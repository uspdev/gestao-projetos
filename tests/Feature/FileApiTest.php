<?php

namespace Tests\Feature;

use App\Models\Media;
use App\Models\Meeting;
use App\Models\Module;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;
use Uspdev\ApiKeys\Contracts\ApiKeyManager;

class FileApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'activitylog.enabled' => false,
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        $this->createSchema();

        Module::query()->create(['name' => 'Reuniões', 'slug' => 'meetings']);
        Module::query()->create(['name' => 'Tarefas', 'slug' => 'tasks']);
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');

        parent::tearDown();
    }

    public function test_project_key_discovers_the_four_file_paths_once_with_download_url(): void
    {
        $project = $this->project('Projeto autorizado');
        $external = $this->project('Projeto externo');
        $task = $this->task($project, 'Tarefa do Projeto');
        $meeting = $this->meeting([$project], 'Reunião do Projeto');

        $projectFile = $this->file($project, 'Documento do Projeto', 'project.pdf', 'application/pdf', 101);
        $taskFile = $this->file($task, 'Documento da Tarefa', 'task.txt', 'text/plain', 102);
        $meetingFile = $this->file($meeting, 'Documento da Reunião', 'meeting.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 103);
        $sharedFile = $this->file($external, 'Documento Compartilhado', 'shared.csv', 'text/csv', 104);

        $meeting->sharedFiles()->attach([$projectFile->id, $taskFile->id, $sharedFile->id]);

        $response = $this->withToken($this->tokenFor($project, 'viewer'))
            ->getJson($this->indexUrl($project))
            ->assertOk()
            ->assertJsonStructure(['data', 'links', 'meta'])
            ->assertJsonCount(4, 'data')
            ->assertJsonPath('meta.total', 4);

        $this->assertEqualsCanonicalizing(
            [$projectFile->uuid, $taskFile->uuid, $meetingFile->uuid, $sharedFile->uuid],
            collect($response->json('data'))->pluck('uuid')->all(),
        );
        $this->assertSame(1, collect($response->json('data'))->where('uuid', $projectFile->uuid)->count());

        $item = collect($response->json('data'))->firstWhere('uuid', $projectFile->uuid);
        $this->assertSame([
            'uuid', 'name', 'extension', 'mime_type', 'size', 'uploaded_at', 'owner', 'download_url',
        ], array_keys($item));
        $this->assertSame([
            'type' => 'project',
            'id' => $project->id,
            'slug' => $project->slug,
            'name' => $project->name,
        ], $item['owner']);
        $this->assertSame('pdf', $item['extension']);
        $this->assertSame(101, $item['size']);
        $this->assertSame(route('api.projects.files.show', [$project, $projectFile->uuid]), $item['download_url']);
        $this->assertArrayNotHasKey('original_name', $item);
        $this->assertArrayNotHasKey('uploaded_by', $item);
        $this->assertArrayNotHasKey('disk', $item);
        $this->assertArrayNotHasKey('path', $item);
        $this->assertArrayNotHasKey('thumbnail', $item);

        $taskItem = collect($response->json('data'))->firstWhere('uuid', $taskFile->uuid);
        $this->assertSame([
            'type' => 'task',
            'id' => $task->id,
            'title' => $task->title,
        ], $taskItem['owner']);

        $meetingItem = collect($response->json('data'))->firstWhere('uuid', $meetingFile->uuid);
        $this->assertSame([
            'type' => 'meeting',
            'id' => $meeting->id,
            'title' => $meeting->title,
        ], $meetingItem['owner']);
    }

    public function test_project_key_downloads_original_file_with_safe_headers(): void
    {
        Storage::fake('files');

        $project = $this->project('Projeto autorizado');
        $file = $this->file(
            $project,
            'Relatório "; confidencial/2026',
            'nome-original-secreto.html',
            'text/html',
            18,
        );
        $contents = '<h1>conteudo</h1>';
        Storage::disk('files')->put($file->getPathRelativeToRoot(), $contents);

        $response = $this->withToken($this->tokenFor($project, 'viewer'))
            ->get($this->downloadUrl($project, $file))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=utf-8')
            ->assertHeader('Content-Length', (string) strlen($contents))
            ->assertHeader('Content-Disposition', 'attachment; filename="Relatorio confidencial 2026.html"')
            ->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->assertSame($contents, $response->streamedContent());
        $headers = json_encode($response->headers->all(), JSON_THROW_ON_ERROR);
        $this->assertStringNotContainsString('nome-original-secreto', $headers);
        $this->assertStringNotContainsString($file->disk, $headers);
        $this->assertStringNotContainsString($file->getPathRelativeToRoot(), $headers);
        $this->assertStringNotContainsString('nome-original-secreto', $response->streamedContent());
        $this->assertStringNotContainsString($file->getPathRelativeToRoot(), $response->streamedContent());
    }

    public function test_download_requires_bearer_files_ability_and_valid_project_scope_and_uuid(): void
    {
        Storage::fake('files');

        $project = $this->project('Projeto protegido');
        $external = $this->project('Projeto externo');
        $file = $this->file($project, 'Arquivo protegido', 'protected.txt', 'text/plain', 8);
        $externalFile = $this->file($external, 'Arquivo externo', 'external.txt', 'text/plain', 7);
        Storage::disk('files')->put($file->getPathRelativeToRoot(), 'conteudo');
        Storage::disk('files')->put($externalFile->getPathRelativeToRoot(), 'externo');

        $this->get($this->downloadUrl($project, $file))->assertUnauthorized();
        $this->withToken($this->tokenFor($project, 'unknown'))
            ->get($this->downloadUrl($project, $file))
            ->assertForbidden();
        $this->withToken($this->tokenFor($external, 'viewer'))
            ->get($this->downloadUrl($project, $file))
            ->assertNotFound();
        $this->withToken($this->tokenFor($project, 'viewer'))
            ->get($this->downloadUrl($project, $externalFile))
            ->assertNotFound();
        $this->get($this->indexUrl($project).'/'.Str::uuid())
            ->assertNotFound();
        $this->get($this->indexUrl($project).'/uuid-invalido')
            ->assertNotFound();
    }

    public function test_download_uses_exactly_the_file_paths_visible_in_the_list(): void
    {
        Storage::fake('files');

        $project = $this->project('Projeto com caminhos');
        $external = $this->project('Projeto externo');
        $task = $this->task($project, 'Tarefa do Projeto');
        $meeting = $this->meeting([$project], 'Reunião do Projeto');

        $projectFile = $this->file($project, 'Direto do Projeto', 'project.txt', 'text/plain', 7);
        $taskOnly = $this->file($task, 'Somente pela Tarefa', 'task.txt', 'text/plain', 7);
        $taskAndMeeting = $this->file($task, 'Tarefa e Reunião', 'alternative.txt', 'text/plain', 7);
        $meetingFile = $this->file($meeting, 'Da Reunião', 'meeting.txt', 'text/plain', 7);
        $sharedFile = $this->file($external, 'Compartilhado', 'shared.txt', 'text/plain', 7);
        $externalFile = $this->file($external, 'Sem compartilhamento', 'external.txt', 'text/plain', 7);
        $meeting->sharedFiles()->attach([$projectFile->id, $taskAndMeeting->id, $sharedFile->id]);
        $files = [$projectFile, $taskOnly, $taskAndMeeting, $meetingFile, $sharedFile, $externalFile];

        foreach ($files as $file) {
            Storage::disk('files')->put($file->getPathRelativeToRoot(), 'arquivo');
        }

        $this->setModule($project, 'tasks', false);
        $this->withToken($this->tokenFor($project, 'viewer'));

        $visibleWithMeetings = collect($this->getJson($this->indexUrl($project))->json('data'))
            ->pluck('uuid')
            ->all();
        $this->assertEqualsCanonicalizing(
            [$projectFile->uuid, $taskAndMeeting->uuid, $meetingFile->uuid, $sharedFile->uuid],
            $visibleWithMeetings,
        );

        foreach ($files as $file) {
            $response = $this->get($this->downloadUrl($project, $file));
            in_array($file->uuid, $visibleWithMeetings, true)
                ? $response->assertOk()
                : $response->assertNotFound();
        }

        $this->setModule($project, 'meetings', false);
        $visibleWithoutModules = collect($this->getJson($this->indexUrl($project))->json('data'))
            ->pluck('uuid')
            ->all();
        $this->assertSame([$projectFile->uuid], $visibleWithoutModules);

        foreach ($files as $file) {
            $response = $this->get($this->downloadUrl($project, $file));
            $file->is($projectFile) ? $response->assertOk() : $response->assertNotFound();
        }
    }

    public function test_images_and_pdfs_are_always_downloaded_with_their_mime_type(): void
    {
        Storage::fake('files');

        $project = $this->project('Projeto com conteúdo binário');
        $image = $this->file($project, 'Imagem pública', 'segredo.png', 'image/png', 8);
        $pdf = $this->file($project, 'Documento público', 'segredo.pdf', 'application/pdf', 8);

        foreach ([$image, $pdf] as $file) {
            Storage::disk('files')->put($file->getPathRelativeToRoot(), 'binario!');
        }

        $this->withToken($this->tokenFor($project, 'contributor'));

        foreach ([$image, $pdf] as $file) {
            $this->get($this->downloadUrl($project, $file))
                ->assertOk()
                ->assertHeader('Content-Type', $file->mime_type)
                ->assertHeader('Content-Disposition', 'attachment; filename="'.Str::ascii($file->display_name).'.'.pathinfo($file->file_name, PATHINFO_EXTENSION).'"')
                ->assertHeader('X-Content-Type-Options', 'nosniff');
        }
    }

    public function test_deleted_sharing_meeting_needs_an_active_alternative_path_for_download(): void
    {
        Storage::fake('files');

        $project = $this->project('Projeto com Reuniões');
        $external = $this->project('Projeto externo');
        $activeMeeting = $this->meeting([$project], 'Reunião ativa');
        $deletedMeeting = $this->meeting([$project], 'Reunião excluída');
        $sharedByBoth = $this->file($external, 'Compartilhado por ambas', 'both.txt', 'text/plain', 7);
        $sharedOnlyByDeleted = $this->file($external, 'Compartilhado pela excluída', 'deleted.txt', 'text/plain', 7);

        $activeMeeting->sharedFiles()->attach($sharedByBoth);
        $deletedMeeting->sharedFiles()->attach([$sharedByBoth->id, $sharedOnlyByDeleted->id]);
        DB::table('meetings')->where('id', $deletedMeeting->id)->update(['deleted_at' => now()]);
        Storage::disk('files')->put($sharedByBoth->getPathRelativeToRoot(), 'visivel');
        Storage::disk('files')->put($sharedOnlyByDeleted->getPathRelativeToRoot(), 'oculto!');

        $this->withToken($this->tokenFor($project, 'viewer'));

        $this->get($this->downloadUrl($project, $sharedByBoth))
            ->assertOk();
        $this->get($this->downloadUrl($project, $sharedOnlyByDeleted))
            ->assertNotFound();
    }

    public function test_list_filters_by_display_name_owner_mime_type_and_inclusive_upload_interval(): void
    {
        $project = $this->project('Projeto filtrado');
        $external = $this->project('Projeto externo');
        $task = $this->task($project, 'Tarefa filtrada');
        $meeting = $this->meeting([$project], 'Reunião filtrada');

        $projectPdf = $this->file(
            $project,
            'Relatorio principal',
            'project.pdf',
            'application/pdf',
            100,
            '2026-09-20 10:00:00',
        );
        $taskText = $this->file(
            $task,
            'Relatorio da tarefa',
            'task.txt',
            'text/plain',
            200,
            '2026-09-21 10:00:00',
        );
        $meetingPdf = $this->file(
            $meeting,
            'Ata da reuniao',
            'meeting.pdf',
            'application/pdf',
            300,
            '2026-09-22 10:00:00',
        );
        $sharedCsv = $this->file(
            $external,
            'Relatorio externo',
            'shared.csv',
            'text/csv',
            400,
            '2026-09-23 10:00:00',
        );
        $meeting->sharedFiles()->attach($sharedCsv);

        $this->withToken($this->tokenFor($project, 'viewer'));

        $search = $this->getJson($this->indexUrl($project).'?search=RELATORIO')->assertOk();
        $this->assertEqualsCanonicalizing(
            [$projectPdf->uuid, $taskText->uuid, $sharedCsv->uuid],
            collect($search->json('data'))->pluck('uuid')->all(),
        );

        $owners = $this->getJson($this->indexUrl($project).'?'.http_build_query([
            'owner_type' => ['task', 'meeting'],
        ]))->assertOk();
        $this->assertEqualsCanonicalizing(
            [$taskText->uuid, $meetingPdf->uuid],
            collect($owners->json('data'))->pluck('uuid')->all(),
        );

        $mimes = $this->getJson($this->indexUrl($project).'?'.http_build_query([
            'mime_type' => ['application/pdf', 'text/csv'],
        ]))->assertOk();
        $this->assertEqualsCanonicalizing(
            [$projectPdf->uuid, $meetingPdf->uuid, $sharedCsv->uuid],
            collect($mimes->json('data'))->pluck('uuid')->all(),
        );

        $interval = $this->getJson($this->indexUrl($project).'?'.http_build_query([
            'uploaded_from' => '2026-09-21T13:00:00Z',
            'uploaded_to' => '2026-09-22T13:00:00Z',
        ]))->assertOk();
        $this->assertEqualsCanonicalizing(
            [$taskText->uuid, $meetingPdf->uuid],
            collect($interval->json('data'))->pluck('uuid')->all(),
        );

        $this->getJson($this->indexUrl($project).'?owner_type=project')
            ->assertOk()
            ->assertJsonPath('meta.total', 2);
    }

    public function test_parent_child_indirect_meeting_and_foreign_project_files_do_not_cross_scope(): void
    {
        $parent = $this->project('Projeto pai');
        $project = $this->project('Projeto autorizado', $parent);
        $child = $this->project('Subprojeto', $project);
        $foreign = $this->project('Projeto alheio');
        $directMeeting = $this->meeting([$project], 'Reunião direta');
        $indirectMeeting = $this->meeting([$parent], 'Reunião indireta');

        $visible = $this->file($directMeeting, 'Visível', 'visible.txt', 'text/plain', 10);
        $parentFile = $this->file($parent, 'Do pai', 'parent.txt', 'text/plain', 20);
        $childFile = $this->file($child, 'Do filho', 'child.txt', 'text/plain', 30);
        $foreignFile = $this->file($foreign, 'Alheio', 'foreign.txt', 'text/plain', 40);
        $indirectFile = $this->file($indirectMeeting, 'Reunião indireta', 'indirect.txt', 'text/plain', 50);
        $indirectMeeting->sharedFiles()->attach($foreignFile);

        $response = $this->withToken($this->tokenFor($project, 'viewer'))
            ->getJson($this->indexUrl($project))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.uuid', $visible->uuid);

        $this->assertEmpty(array_intersect(
            [$parentFile->uuid, $childFile->uuid, $foreignFile->uuid, $indirectFile->uuid],
            collect($response->json('data'))->pluck('uuid')->all(),
        ));

        $this->withToken($this->tokenFor($foreign, 'viewer'))
            ->getJson($this->indexUrl($project))
            ->assertNotFound();
    }

    public function test_module_paths_are_disabled_but_an_active_alternative_path_keeps_the_file_visible(): void
    {
        $project = $this->project('Projeto com módulos');
        $external = $this->project('Projeto de origem');
        $task = $this->task($project, 'Tarefa do Projeto');
        $meeting = $this->meeting([$project], 'Reunião do Projeto');

        $projectFile = $this->file($project, 'Direto do Projeto', 'project.txt', 'text/plain', 10);
        $taskOnly = $this->file($task, 'Somente pela Tarefa', 'task-only.txt', 'text/plain', 20);
        $taskShared = $this->file($task, 'Tarefa e compartilhamento', 'task-shared.txt', 'text/plain', 30);
        $meetingFile = $this->file($meeting, 'Somente pela Reunião', 'meeting.txt', 'text/plain', 40);
        $externalShared = $this->file($external, 'Somente compartilhado', 'shared.txt', 'text/plain', 50);
        $meeting->sharedFiles()->attach([$taskShared->id, $externalShared->id]);

        $this->setModule($project, 'tasks', false);
        $this->withToken($this->tokenFor($project, 'viewer'));

        $withoutTasks = $this->getJson($this->indexUrl($project))->assertOk();
        $this->assertEqualsCanonicalizing(
            [$projectFile->uuid, $taskShared->uuid, $meetingFile->uuid, $externalShared->uuid],
            collect($withoutTasks->json('data'))->pluck('uuid')->all(),
        );
        $this->assertNotContains($taskOnly->uuid, collect($withoutTasks->json('data'))->pluck('uuid')->all());

        $this->setModule($project, 'meetings', false);
        $this->getJson($this->indexUrl($project))
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.uuid', $projectFile->uuid);

        $this->setModule($project, 'tasks', true);
        $withoutMeetings = $this->getJson($this->indexUrl($project))->assertOk();
        $this->assertEqualsCanonicalizing(
            [$projectFile->uuid, $taskOnly->uuid, $taskShared->uuid],
            collect($withoutMeetings->json('data'))->pluck('uuid')->all(),
        );
    }

    public function test_deleted_resources_only_expose_files_through_an_active_alternative_path(): void
    {
        Storage::fake('files');

        $project = $this->project('Projeto com exclusões');
        $external = $this->project('Projeto externo');
        $task = $this->task($project, 'Tarefa excluída');
        $meeting = $this->meeting([$project], 'Reunião ativa');
        $deletedMeeting = $this->meeting([$project], 'Reunião excluída');

        $projectFile = $this->file($project, 'Direto do Projeto', 'project.txt', 'text/plain', 10);
        $deletedTaskFile = $this->file($task, 'Da Tarefa excluída', 'task.txt', 'text/plain', 20);
        $deletedMeetingFile = $this->file($deletedMeeting, 'Da Reunião excluída', 'meeting.txt', 'text/plain', 30);
        $sharedByActiveMeeting = $this->file($external, 'Compartilhado ativo', 'active.txt', 'text/plain', 40);
        $sharedByDeletedMeeting = $this->file($external, 'Compartilhado excluído', 'deleted.txt', 'text/plain', 50);
        $meeting->sharedFiles()->attach([$deletedTaskFile->id, $sharedByActiveMeeting->id]);
        $deletedMeeting->sharedFiles()->attach($sharedByDeletedMeeting);

        DB::table('tasks')->where('id', $task->id)->update(['deleted_at' => now()]);
        DB::table('meetings')->where('id', $deletedMeeting->id)->update(['deleted_at' => now()]);
        Storage::disk('files')->put($deletedTaskFile->getPathRelativeToRoot(), 'alternativo');

        $response = $this->withToken($this->tokenFor($project, 'viewer'))
            ->getJson($this->indexUrl($project))
            ->assertOk();

        $this->assertEqualsCanonicalizing(
            [$projectFile->uuid, $deletedTaskFile->uuid, $sharedByActiveMeeting->uuid],
            collect($response->json('data'))->pluck('uuid')->all(),
        );
        $this->assertEmpty(array_intersect(
            [$deletedMeetingFile->uuid, $sharedByDeletedMeeting->uuid],
            collect($response->json('data'))->pluck('uuid')->all(),
        ));
        $this->get($this->downloadUrl($project, $deletedTaskFile))
            ->assertOk()
            ->assertStreamedContent('alternativo');
    }

    public function test_route_requires_files_read_and_accepts_both_project_key_roles(): void
    {
        $project = $this->project('Projeto protegido');
        $file = $this->file($project, 'Arquivo protegido', 'protected.txt', 'text/plain', 10);

        $this->getJson($this->indexUrl($project))->assertUnauthorized();
        $this->withToken($this->tokenFor($project, 'unknown'))
            ->getJson($this->indexUrl($project))->assertForbidden();
        $this->withToken($this->tokenFor($project, 'viewer'))
            ->getJson($this->indexUrl($project))->assertOk()->assertJsonPath('data.0.uuid', $file->uuid);
        $this->withToken($this->tokenFor($project, 'contributor'))
            ->getJson($this->indexUrl($project))->assertOk()->assertJsonPath('data.0.uuid', $file->uuid);
    }

    public function test_list_uses_stable_upload_order_and_laravel_pagination(): void
    {
        $project = $this->project('Projeto paginado');
        $files = [];

        foreach (range(1, 22) as $number) {
            $files[] = $this->file(
                $project,
                'Arquivo '.$number,
                'file-'.$number.'.txt',
                'text/plain',
                $number,
                $number === 22 ? '2026-09-22 12:00:00' : '2026-09-21 12:00:00',
            );
        }

        $this->withToken($this->tokenFor($project, 'viewer'));
        $first = $this->getJson($this->indexUrl($project))
            ->assertOk()
            ->assertJsonStructure(['data', 'links', 'meta'])
            ->assertJsonPath('meta.total', 22)
            ->assertJsonPath('meta.per_page', 20)
            ->assertJsonPath('meta.last_page', 2);
        $this->assertSame(
            array_merge(
                [$files[21]->uuid],
                array_map(fn (Media $file): string => $file->uuid, array_reverse(array_slice($files, 2, 19))),
            ),
            collect($first->json('data'))->pluck('uuid')->all(),
        );

        $second = $this->getJson($this->indexUrl($project).'?page=2')->assertOk();
        $this->assertSame(
            [$files[1]->uuid, $files[0]->uuid],
            collect($second->json('data'))->pluck('uuid')->all(),
        );
        $this->getJson($this->indexUrl($project).'?per_page=1')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('meta.per_page', 1);
        $this->getJson($this->indexUrl($project).'?per_page=100')
            ->assertOk()->assertJsonCount(22, 'data')->assertJsonPath('meta.per_page', 100);
    }

    public function test_invalid_filters_intervals_and_pagination_return_laravel_validation_errors(): void
    {
        $project = $this->project('Projeto validado');
        $this->withToken($this->tokenFor($project, 'viewer'));

        foreach ([
            [['owner_type' => ['folder']], 'owner_type.0'],
            [['owner_type' => ['']], 'owner_type.0'],
            [['mime_type' => ['']], 'mime_type.0'],
            [['search' => ['array']], 'search'],
            [['uploaded_from' => '2026-09-21'], 'uploaded_from'],
            [['uploaded_from' => '2026-02-30T10:00:00Z'], 'uploaded_from'],
            [['uploaded_from' => '2026-09-22T00:00:00Z', 'uploaded_to' => '2026-09-21T00:00:00Z'], 'uploaded_to'],
            [['page' => 0], 'page'],
            [['page' => 'abc'], 'page'],
            [['per_page' => 0], 'per_page'],
            [['per_page' => 101], 'per_page'],
        ] as [$query, $field]) {
            $this->getJson($this->indexUrl($project).'?'.http_build_query($query))
                ->assertUnprocessable()
                ->assertJsonStructure(['message', 'errors'])
                ->assertJsonValidationErrors($field);
        }
    }

    private function project(string $name, ?Project $parent = null): Project
    {
        $project = new Project();
        $project->forceFill([
            'name' => $name,
            'slug' => str()->slug($name),
            'status' => 'ACTIVE',
            'parent_id' => $parent?->id,
            'permission_inheritance' => 'FULL',
            'visibility' => 'PRIVATE',
        ]);
        $project->save();
        $project->projectModules()->update(['enabled' => true]);

        return $project;
    }

    private function task(Project $project, string $title): Task
    {
        return Task::query()->create([
            'project_id' => $project->id,
            'title' => $title,
            'status' => 'NEW',
        ]);
    }

    /** @param list<Project> $projects */
    private function meeting(array $projects, string $title): Meeting
    {
        $meeting = Meeting::query()->create([
            'title' => $title,
            'scheduled_at' => '2026-09-21 12:00:00',
            'status' => 'SCHEDULED',
        ]);
        $meeting->projects()->attach(collect($projects)->pluck('id'));

        return $meeting;
    }

    private function file(
        Project|Task|Meeting $owner,
        string $name,
        string $originalName,
        string $mimeType,
        int $size,
        string $uploadedAt = '2026-09-21 12:00:00',
    ): Media {
        $uuid = (string) Str::uuid();
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $id = DB::table('media')->insertGetId([
            'model_type' => $owner->getMorphClass(),
            'model_id' => $owner->getKey(),
            'uuid' => $uuid,
            'collection_name' => 'default',
            'name' => $name,
            'original_name' => $originalName,
            'file_name' => $uuid.($extension === '' ? '' : '.'.$extension),
            'mime_type' => $mimeType,
            'disk' => 'files',
            'conversions_disk' => 'files',
            'size' => $size,
            'manipulations' => '[]',
            'custom_properties' => '[]',
            'generated_conversions' => '[]',
            'responsive_images' => '[]',
            'order_column' => 1,
            'uploaded_by' => null,
            'created_at' => $uploadedAt,
            'updated_at' => $uploadedAt,
        ]);

        return Media::query()->findOrFail($id);
    }

    private function tokenFor(Project $project, string $role): string
    {
        return app(ApiKeyManager::class)->create(
            $project,
            'Credencial de teste',
            'integration',
            $role,
        )->plainTextToken();
    }

    private function indexUrl(Project $project): string
    {
        return '/api/projects/'.$project->slug.'/files';
    }

    private function downloadUrl(Project $project, Media $file): string
    {
        return $this->indexUrl($project).'/'.$file->uuid;
    }

    private function setModule(Project $project, string $slug, bool $enabled): void
    {
        DB::table('project_modules')
            ->where('project_id', $project->id)
            ->where('module_id', Module::query()->where('slug', $slug)->value('id'))
            ->update(['enabled' => $enabled, 'updated_at' => now()]);
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });
        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status');
            $table->foreignId('parent_id')->nullable();
            $table->foreignId('project_type_id')->nullable();
            $table->string('visibility')->default('PRIVATE');
            $table->string('permission_inheritance')->default('FULL');
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->foreignId('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('modules', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->timestamps();
        });
        Schema::create('project_modules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id');
            $table->foreignId('module_id');
            $table->boolean('enabled')->default(true);
            $table->timestamps();
            $table->unique(['project_id', 'module_id']);
        });
        Schema::create('tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('priority')->nullable();
            $table->string('status');
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->boolean('deleted_via_project')->default(false);
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->foreignId('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('meetings', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->dateTime('scheduled_at')->nullable();
            $table->string('location')->nullable();
            $table->longText('notes')->nullable();
            $table->longText('ata')->nullable();
            $table->longText('transcription')->nullable();
            $table->string('status');
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->foreignId('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('meeting_projects', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('meeting_id');
            $table->foreignId('project_id');
            $table->unique(['meeting_id', 'project_id']);
        });

        (require database_path('migrations/2026_07_21_090000_create_media_table.php'))->up();
        (require database_path('migrations/2026_07_22_090000_create_meeting_file_shares_table.php'))->up();
        (require database_path('migrations/2026_07_13_000000_create_uspdev_api_keys_table.php'))->up();
    }
}
