<?php

namespace Tests\Feature;

use App\Models\Meeting;
use App\Models\Media;
use App\Models\Module;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Tests\TestCase;
use Uspdev\ApiKeys\Contracts\ApiKeyManager;

class MeetingApiTest extends TestCase
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

    public function test_shared_meeting_is_visible_in_full_only_to_directly_linked_projects(): void
    {
        $project = $this->project('Projeto autorizado');
        $other = $this->project('Projeto externo');
        $child = $this->project('Subprojeto', $project);
        $meeting = $this->meeting([$project, $other], [
            'notes' => 'Anotações prévias integrais',
            'ata' => 'Ata integral',
            'transcription' => 'Transcrição integral',
        ]);

        $this->withToken($this->tokenFor($project, 'viewer'))
            ->getJson($this->indexUrl($project))
            ->assertOk()
            ->assertJsonPath('data.0.id', $meeting->id)
            ->assertJsonPath('data.0.projects.1.id', $other->id)
            ->assertJsonMissingPath('data.0.notes');

        $this->getJson($this->showUrl($project, $meeting))
            ->assertOk()
            ->assertJsonPath('data.notes', 'Anotações prévias integrais')
            ->assertJsonPath('data.ata', 'Ata integral')
            ->assertJsonPath('data.transcription', 'Transcrição integral');

        $this->withToken($this->tokenFor($other, 'contributor'))
            ->getJson($this->showUrl($other, $meeting))
            ->assertOk()
            ->assertJsonPath('data.projects.0.id', $project->id);

        $this->withToken($this->tokenFor($child, 'viewer'))
            ->getJson($this->indexUrl($child))
            ->assertOk()
            ->assertJsonCount(0, 'data');

        $this->getJson($this->showUrl($child, $meeting))->assertNotFound();
    }

    public function test_detail_contains_ordered_agenda_external_references_and_active_comments_with_author_email(): void
    {
        $project = $this->project('Projeto principal');
        $external = $this->project('Projeto externo');
        $meeting = $this->meeting([$project, $external], [
            'title' => 'Discussão integral',
            'location' => 'Sala 1',
            'status' => 'COMPLETED',
            'notes' => 'Anotações prévias completas',
            'ata' => 'Ata completa',
            'transcription' => 'Transcrição completa',
        ]);
        $taskId = DB::table('tasks')->insertGetId([
            'project_id' => $external->id,
            'title' => 'Tarefa externa',
            'status' => 'NEW',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('meeting_items')->insert([
            $this->agendaRow($meeting, 3, Task::class, $taskId, null, 'Anotações da tarefa'),
            $this->agendaRow($meeting, 2, null, null, 'Tema independente', 'Anotações próprias'),
            $this->agendaRow($meeting, 1, 'project', $external->id, null, 'Anotações do projeto'),
        ]);
        $agendaItems = DB::table('meeting_items')
            ->where('meeting_id', $meeting->id)
            ->orderBy('order')
            ->get()
            ->keyBy('order');
        $userId = DB::table('users')->insertGetId([
            'name' => 'Ana Autora',
            'email' => 'segredo@example.test',
            'password' => 'secret',
        ]);
        foreach ([
            ['Primeiro comentário', true, '2026-09-20 10:00:00'],
            ['Comentário oculto', false, '2026-09-20 10:30:00'],
            ['Segundo comentário', true, '2026-09-20 11:00:00'],
        ] as [$text, $active, $date]) {
            DB::table('comments')->insert([
                'user_id' => $userId,
                'commentable_type' => 'meeting',
                'commentable_id' => $meeting->id,
                'text' => $text,
                'is_active' => $active,
                'created_at' => $date,
                'updated_at' => $date,
            ]);
        }
        $comments = DB::table('comments')
            ->where('commentable_type', 'meeting')
            ->where('commentable_id', $meeting->id)
            ->where('is_active', true)
            ->orderBy('created_at')
            ->get();

        $token = $this->tokenFor($project, 'viewer');
        $list = $this->withToken($token)->getJson($this->indexUrl($project))->assertOk();
        $this->assertEqualsCanonicalizing([
            'id', 'title', 'status', 'scheduled_at', 'location', 'projects',
            'created_at', 'updated_at', 'web_url',
        ], array_keys($list->json('data.0')));
        $list->assertJsonPath('data.0.status', ['value' => 'COMPLETED', 'label' => 'Concluída'])
            ->assertJsonPath('data.0.web_url', route('projects.meetings.show', [$project, $meeting]));

        $detail = $this->getJson($this->showUrl($project, $meeting))->assertOk();
        $detail->assertJsonPath('data.notes', 'Anotações prévias completas')
            ->assertJsonPath('data.ata', 'Ata completa')
            ->assertJsonPath('data.transcription', 'Transcrição completa')
            ->assertJsonPath('data.agenda.0.id', $agendaItems[1]->id)
            ->assertJsonPath('data.agenda.0.position', 1)
            ->assertJsonPath('data.agenda.0.type', 'project')
            ->assertJsonPath('data.agenda.0.reference', [
                'type' => 'project',
                'id' => $external->id,
                'slug' => $external->slug,
                'name' => $external->name,
                'web_url' => route('projects.show', $external),
            ])
            ->assertJsonPath('data.agenda.1.type', 'independent')
            ->assertJsonPath('data.agenda.1.title', 'Tema independente')
            ->assertJsonPath('data.agenda.1.notes', 'Anotações próprias')
            ->assertJsonPath('data.agenda.1.reference', null)
            ->assertJsonPath('data.agenda.2.type', 'task')
            ->assertJsonPath('data.agenda.2.reference', [
                'type' => 'task',
                'id' => $taskId,
                'title' => 'Tarefa externa',
                'web_url' => route('tasks.show', $taskId),
            ])
            ->assertJsonCount(2, 'data.comments')
            ->assertJsonPath('data.comments.0.id', $comments[0]->id)
            ->assertJsonPath('data.comments.0.text', 'Primeiro comentário')
            ->assertJsonPath('data.comments.0.created_at', '2026-09-20T13:00:00.000000Z')
            ->assertJsonPath('data.comments.0.updated_at', '2026-09-20T13:00:00.000000Z')
            ->assertJsonPath('data.comments.1.text', 'Segundo comentário')
            ->assertJsonPath('data.comments.0.author', [
                'id' => $userId,
                'name' => 'Ana Autora',
                'email' => 'segredo@example.test',
            ])
            ->assertJsonPath('data.files', ['owned' => [], 'shared' => []])
            ->assertJsonPath('data.links', ['owned' => [], 'shared' => []])
            ->assertJsonPath('data.incoming_mentions', [
                'locations_count' => 0,
                'sources_count' => 0,
                'sources' => [],
            ]);
        $this->assertEqualsCanonicalizing(
            ['id', 'text', 'created_at', 'updated_at', 'author'],
            array_keys($detail->json('data.comments.0')),
        );

        $this->getJson('/api/projects/'.$project->slug.'/tasks/'.$taskId)->assertNotFound();
        $this->getJson('/api/projects/'.$external->slug)->assertNotFound();
    }

    public function test_routes_require_meetings_read_and_accept_both_project_key_roles(): void
    {
        $project = $this->project('Projeto protegido');
        $meeting = $this->meeting([$project]);

        $this->getJson($this->indexUrl($project))->assertUnauthorized();
        $this->withToken($this->tokenFor($project, 'unknown'))
            ->getJson($this->indexUrl($project))->assertForbidden();
        $this->withToken($this->tokenFor($project, 'viewer'))
            ->getJson($this->showUrl($project, $meeting))->assertOk();
        $this->withToken($this->tokenFor($project, 'contributor'))
            ->getJson($this->indexUrl($project))->assertOk()->assertJsonPath('data.0.id', $meeting->id);
    }

    public function test_detail_exposes_owned_shared_content_and_only_project_scoped_incoming_mentions(): void
    {
        $project = $this->project('Projeto autorizado');
        $foreign = $this->project('Projeto externo');
        $meeting = $this->meeting([$project], ['title' => 'Reunião completa']);
        $task = Task::query()->create([
            'project_id' => $project->id,
            'title' => 'Tarefa de origem',
            'status' => 'NEW',
        ]);
        $foreignTask = Task::query()->create([
            'project_id' => $foreign->id,
            'title' => 'Tarefa externa',
            'status' => 'NEW',
        ]);

        $ownedFile = $this->file($meeting, 'Arquivo próprio.pdf');
        $sharedFile = $this->file($task, 'Arquivo compartilhado.txt');
        $meeting->sharedFiles()->attach($sharedFile, ['shared_by' => null]);

        $ownedLink = $meeting->links()->create([
            'name' => 'Link próprio',
            'url' => 'https://example.test/owned',
            'created_by' => null,
        ]);
        $sharedLink = $task->links()->create([
            'name' => 'Link compartilhado',
            'url' => 'https://example.test/shared',
            'created_by' => null,
        ]);
        $meeting->sharedLinks()->attach($sharedLink, ['shared_by' => null]);

        DB::table('mentions')->insert([
            [
                'source_type' => 'task',
                'source_id' => $task->id,
                'source_field' => 'description',
                'target_type' => 'meeting',
                'target_id' => $meeting->id,
            ],
            [
                'source_type' => 'task',
                'source_id' => $foreignTask->id,
                'source_field' => 'description',
                'target_type' => 'meeting',
                'target_id' => $meeting->id,
            ],
        ]);

        $response = $this->withToken($this->tokenFor($project, 'viewer'))
            ->getJson($this->showUrl($project, $meeting))
            ->assertOk();

        $response
            ->assertJsonPath('data.files.owned.0.uuid', $ownedFile->uuid)
            ->assertJsonPath('data.files.shared.0.uuid', $sharedFile->uuid)
            ->assertJsonPath('data.links.owned.0.uuid', $ownedLink->uuid)
            ->assertJsonPath('data.links.shared.0.uuid', $sharedLink->uuid)
            ->assertJsonPath('data.incoming_mentions.locations_count', 1)
            ->assertJsonPath('data.incoming_mentions.sources_count', 1)
            ->assertJsonPath('data.incoming_mentions.sources.0.source.id', $task->id)
            ->assertJsonPath('data.incoming_mentions.sources.0.locations.0.field', 'description');
    }

    public function test_list_filters_status_date_and_case_insensitive_title_or_location_search(): void
    {
        $project = $this->project('Projeto filtrado');
        $first = $this->meeting([$project], [
            'title' => 'Planejamento', 'location' => 'Sala INTEGRACAO',
            'status' => 'COMPLETED', 'scheduled_at' => '2026-09-20 10:00:00',
        ]);
        $second = $this->meeting([$project], [
            'title' => 'INTEGRACAO de dados', 'location' => 'Sala 2',
            'status' => 'SCHEDULED', 'scheduled_at' => '2026-09-21 10:00:00',
        ]);
        $this->meeting([$project], [
            'title' => 'Outra pauta', 'status' => 'COMPLETED',
            'scheduled_at' => '2026-09-22 10:00:00',
        ]);

        $url = $this->indexUrl($project).'?'.http_build_query([
            'status' => ['COMPLETED', 'SCHEDULED'],
            'scheduled_from' => '2026-09-20T13:00:00Z',
            'scheduled_to' => '2026-09-21T13:00:00Z',
            'search' => 'integracao',
        ]);
        $response = $this->withToken($this->tokenFor($project, 'viewer'))
            ->getJson($url)->assertOk()->assertJsonPath('meta.total', 2);
        $this->assertSame([$second->id, $first->id], collect($response->json('data'))->pluck('id')->all());

        $this->getJson($this->indexUrl($project).'?status=COMPLETED')
            ->assertOk()->assertJsonPath('meta.total', 2);

        $this->getJson($this->indexUrl($project).'?'.http_build_query([
            'scheduled_from' => '2026-09-20T15:00:00+02:00',
            'scheduled_to' => '2026-09-20T15:00:00+02:00',
        ]))->assertOk()->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $first->id);

        $this->getJson($this->indexUrl($project).'?'.http_build_query([
            'scheduled_from' => '2026-09-20T13:00:00.500Z',
            'scheduled_to' => '2026-09-21T13:00:00.500Z',
        ]))->assertOk()->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.id', $second->id);
    }

    public function test_list_includes_completed_meetings_and_paginates_by_date_then_id(): void
    {
        $project = $this->project('Projeto paginado');
        $meetings = [];
        foreach (range(1, 22) as $number) {
            $meetings[] = $this->meeting([$project], [
                'title' => 'Reunião '.$number,
                'status' => $number === 1 ? 'COMPLETED' : 'SCHEDULED',
                'scheduled_at' => $number === 22 ? '2026-09-22 12:00:00' : '2026-09-21 12:00:00',
            ]);
        }
        $token = $this->tokenFor($project, 'viewer');
        $first = $this->withToken($token)->getJson($this->indexUrl($project))
            ->assertOk()->assertJsonStructure(['data', 'links', 'meta'])
            ->assertJsonPath('meta.total', 22)->assertJsonPath('meta.per_page', 20)
            ->assertJsonPath('meta.last_page', 2);
        $this->assertSame(
            array_reverse(array_map(fn (Meeting $meeting) => $meeting->id, array_slice($meetings, 2))),
            collect($first->json('data'))->pluck('id')->all(),
        );
        $second = $this->getJson($this->indexUrl($project).'?page=2')->assertOk();
        $this->assertSame([$meetings[1]->id, $meetings[0]->id], collect($second->json('data'))->pluck('id')->all());
        $second->assertJsonPath('data.1.status.value', 'COMPLETED');
        $this->getJson($this->indexUrl($project).'?per_page=1')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('meta.per_page', 1);
        $this->getJson($this->indexUrl($project).'?per_page=100')
            ->assertOk()->assertJsonCount(22, 'data')->assertJsonPath('meta.per_page', 100);
    }

    public function test_invalid_filters_and_pagination_return_validation_errors(): void
    {
        $project = $this->project('Projeto validado');
        $this->withToken($this->tokenFor($project, 'viewer'));

        foreach ([
            [['status' => 'UNKNOWN'], 'status.0'],
            [['status' => ['']], 'status.0'],
            [['scheduled_from' => '2026-09-21'], 'scheduled_from'],
            [['scheduled_from' => '2026-02-30T10:00:00Z'], 'scheduled_from'],
            [['scheduled_from' => '2026-09-22T00:00:00Z', 'scheduled_to' => '2026-09-21T00:00:00Z'], 'scheduled_to'],
            [['search' => ['array']], 'search'],
            [['page' => 0], 'page'],
            [['per_page' => 0], 'per_page'],
            [['per_page' => 101], 'per_page'],
        ] as [$query, $field]) {
            $this->getJson($this->indexUrl($project).'?'.http_build_query($query))
                ->assertUnprocessable()->assertJsonStructure(['message', 'errors'])
                ->assertJsonValidationErrors($field);
        }
    }

    public function test_foreign_missing_and_deleted_meetings_are_not_exposed(): void
    {
        $project = $this->project('Projeto autorizado');
        $foreign = $this->project('Projeto alheio');
        $visible = $this->meeting([$project]);
        $deleted = $this->meeting([$project], ['title' => 'Reunião excluída']);
        $outside = $this->meeting([$foreign]);
        DB::table('meetings')->where('id', $deleted->id)->update(['deleted_at' => now()]);
        $this->withToken($this->tokenFor($project, 'viewer'));

        $response = $this->getJson($this->indexUrl($project))
            ->assertOk()->assertJsonCount(1, 'data');
        $this->assertSame([$visible->id], collect($response->json('data'))->pluck('id')->all());
        $this->getJson($this->showUrl($project, $deleted))->assertNotFound();
        $this->getJson($this->showUrl($project, $outside))->assertNotFound();
        $this->getJson($this->indexUrl($foreign))->assertNotFound();
        $this->getJson($this->indexUrl($project).'/999999')->assertNotFound();
    }

    public function test_disabled_module_returns_conflict_only_after_project_scope_check(): void
    {
        $project = $this->project('Projeto sem Reuniões');
        $foreign = $this->project('Outro Projeto');
        $meeting = $this->meeting([$project]);
        $project->projectModules()->update(['enabled' => false]);
        $expected = [
            'message' => 'O módulo de Reuniões está desabilitado neste Projeto.',
            'code' => 'meetings_module_disabled',
        ];

        $this->withToken($this->tokenFor($project, 'viewer'))
            ->getJson($this->indexUrl($project))->assertStatus(409)->assertExactJson($expected);
        $this->getJson($this->showUrl($project, $meeting))->assertStatus(409)->assertExactJson($expected);
        $this->withToken($this->tokenFor($foreign, 'viewer'))
            ->getJson($this->indexUrl($project))->assertNotFound();
        $this->getJson($this->showUrl($project, $meeting))->assertNotFound();
    }

    private function agendaRow(
        Meeting $meeting,
        int $order,
        ?string $type,
        ?int $id,
        ?string $title,
        ?string $notes,
    ): array {
        return [
            'meeting_id' => $meeting->id,
            'order' => $order,
            'discussable_type' => $type,
            'discussable_id' => $id,
            'title' => $title,
            'notes' => $notes,
            'created_at' => now(),
            'updated_at' => now(),
        ];
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

    private function meeting(array $projects, array $attributes = []): Meeting
    {
        $meeting = Meeting::query()->create(array_merge([
            'title' => 'Reunião compartilhada',
            'scheduled_at' => '2026-09-21 12:00:00',
            'status' => 'SCHEDULED',
        ], $attributes));
        DB::table('meeting_projects')->insert(array_map(
            fn (Project $project) => ['meeting_id' => $meeting->id, 'project_id' => $project->id],
            $projects,
        ));

        return $meeting;
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

    private function file(Meeting|Task $owner, string $name): Media
    {
        $uuid = (string) Str::uuid();
        $extension = pathinfo($name, PATHINFO_EXTENSION);
        $id = DB::table('media')->insertGetId([
            'model_type' => $owner->getMorphClass(),
            'model_id' => $owner->getKey(),
            'uuid' => $uuid,
            'collection_name' => 'default',
            'name' => $name,
            'original_name' => $name,
            'file_name' => $uuid.'.'.$extension,
            'mime_type' => $extension === 'pdf' ? 'application/pdf' : 'text/plain',
            'disk' => 'files',
            'conversions_disk' => 'files',
            'size' => 100,
            'manipulations' => '[]',
            'custom_properties' => '[]',
            'generated_conversions' => '[]',
            'responsive_images' => '[]',
            'order_column' => 1,
            'uploaded_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Media::query()->findOrFail($id);
    }

    private function indexUrl(Project $project): string
    {
        return '/api/projects/'.$project->slug.'/meetings';
    }

    private function showUrl(Project $project, Meeting $meeting): string
    {
        return $this->indexUrl($project).'/'.$meeting->id;
    }

    private function createSchema(): void
    {
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
        Schema::create('meeting_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('meeting_id');
            $table->string('discussable_type')->nullable();
            $table->unsignedBigInteger('discussable_id')->nullable();
            $table->string('title')->nullable();
            $table->unsignedInteger('order');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('password')->nullable();
            $table->timestamps();
        });
        Schema::create('comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->string('commentable_type');
            $table->unsignedBigInteger('commentable_id');
            $table->foreignId('parent_id')->nullable();
            $table->text('text');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id');
            $table->string('title');
            $table->string('status');
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->foreignId('deleted_by')->nullable();
            $table->boolean('deleted_via_project')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        (require database_path('migrations/2026_07_21_090000_create_media_table.php'))->up();
        (require database_path('migrations/2026_07_22_090000_create_meeting_file_shares_table.php'))->up();
        (require database_path('migrations/2026_07_23_090000_create_mentions_table.php'))->up();
        (require database_path('migrations/2026_08_17_090000_create_links_table.php'))->up();
        (require database_path('migrations/2026_08_17_090100_create_meeting_link_shares_table.php'))->up();
        (require database_path('migrations/2026_07_13_000000_create_uspdev_api_keys_table.php'))->up();
    }
}
