<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Meeting;
use App\Models\MeetingItem;
use App\Models\Comment;
use App\Models\Mention;
use App\Models\Task;
use App\Models\User;
use App\Services\FileContextResolver;
use App\Services\Mentions\MentionManager;
use App\Services\MarkdownRenderer;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class FileReferencesAndMeetingSharesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'http://localhost',
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');
        $this->createSchema();
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');

        parent::tearDown();
    }

    public function test_project_file_selector_returns_only_files_owned_by_the_project_that_the_user_can_view(): void
    {
        Storage::fake('files');
        Queue::fake();

        $user = $this->user('Pessoa colaboradora');
        $project = $this->projectWithMember('Projeto atual', $user);
        $otherProject = $this->projectWithMember('Outro projeto', $user);

        $this->actingAs($user);
        $selected = $project
            ->addMedia(UploadedFile::fake()->createWithContent('decisao.pdf', 'conteudo'))
            ->toMediaCollection();
        $excluded = $otherProject
            ->addMedia(UploadedFile::fake()->createWithContent('outro.pdf', 'conteudo'))
            ->toMediaCollection();

        $response = $this->getJson(route('files.selectable', [
            'context_type' => 'project',
            'context_id' => $project->id,
        ]));

        $response
            ->assertOk()
            ->assertJsonPath('results.0.uuid', $selected->uuid)
            ->assertJsonPath('results.0.name', 'decisao')
            ->assertJsonMissing(['uuid' => $excluded->uuid]);
    }

    public function test_markdown_file_reference_url_navigates_to_the_project_file_card(): void
    {
        Storage::fake('files');
        Queue::fake();

        $user = $this->user('Pessoa autorizada');
        $project = $this->projectWithMember('Projeto com referência', $user);

        $this->actingAs($user);
        $media = $project
            ->addMedia(UploadedFile::fake()->createWithContent('registro.pdf', 'conteudo'))
            ->toMediaCollection();

        $destination = route('projects.show', $project).'#file-'.$media->uuid;

        $this->get(route('files.show', [
            'uuid' => $media->uuid,
            'context_type' => 'project',
            'context_id' => $project->id,
        ]))->assertRedirect($destination);

        $this->getJson(route('files.navigation', [
            'uuid' => $media->uuid,
            'context_type' => 'project',
            'context_id' => $project->id,
        ]))
            ->assertOk()
            ->assertExactJson([
                'url' => $destination,
                'opens_new_tab' => false,
            ]);

        $this->get(route('files.show', ['uuid' => $media->uuid]))
            ->assertRedirect($destination);

        $this->getJson(route('files.navigation', ['uuid' => $media->uuid]))
            ->assertOk()
            ->assertExactJson([
                'url' => $destination,
                'opens_new_tab' => true,
            ]);

        $this->get(route('files.download', ['uuid' => $media->uuid]))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_task_context_uses_the_task_for_its_own_file_and_the_project_for_a_project_file(): void
    {
        Storage::fake('files');
        Queue::fake();

        $user = $this->user('Pessoa da tarefa');
        $project = $this->projectWithMember('Projeto da tarefa navegável', $user);
        $this->enableModule($project, 'tasks');
        $task = Task::query()->create([
            'project_id' => $project->id,
            'title' => 'Tarefa navegável',
            'status' => 'NEW',
        ]);

        $this->actingAs($user);
        $taskFile = $task
            ->addMedia(UploadedFile::fake()->createWithContent('tarefa.txt', 'tarefa'))
            ->toMediaCollection();
        $projectFile = $project
            ->addMedia(UploadedFile::fake()->createWithContent('projeto.txt', 'projeto'))
            ->toMediaCollection();
        $context = [
            'context_type' => 'task',
            'context_id' => $task->id,
        ];

        $this->get(route('files.show', ['uuid' => $taskFile->uuid, ...$context]))
            ->assertRedirect(route('tasks.show', $task).'#file-'.$taskFile->uuid);
        $this->get(route('files.show', ['uuid' => $projectFile->uuid, ...$context]))
            ->assertRedirect(route('projects.show', $project).'#file-'.$projectFile->uuid);

        $this->getJson(route('files.navigation', ['uuid' => $taskFile->uuid, ...$context]))
            ->assertOk()
            ->assertJsonPath('url', route('tasks.show', $task).'#file-'.$taskFile->uuid)
            ->assertJsonPath('opens_new_tab', false);
        $this->getJson(route('files.navigation', ['uuid' => $projectFile->uuid, ...$context]))
            ->assertOk()
            ->assertJsonPath('url', route('projects.show', $project).'#file-'.$projectFile->uuid)
            ->assertJsonPath('opens_new_tab', true);
    }

    public function test_file_context_resolver_returns_files_from_the_source_context(): void
    {
        Storage::fake('files');
        Queue::fake();

        $user = $this->user('Pessoa do contexto de arquivos');
        $project = $this->projectWithMember('Projeto do contexto', $user);
        $this->enableModule($project, 'tasks');
        $task = Task::query()->create([
            'project_id' => $project->id,
            'title' => 'Tarefa do contexto',
            'status' => 'NEW',
        ]);
        $meeting = Meeting::query()->create([
            'title' => 'Reunião do contexto',
            'status' => 'DRAFT',
        ]);
        $meeting->projects()->attach($project);
        $meetingItem = MeetingItem::query()->create([
            'meeting_id' => $meeting->id,
            'title' => 'Item do contexto',
            'order' => 1,
        ]);

        $this->actingAs($user);
        $projectFile = $project
            ->addMedia(UploadedFile::fake()->createWithContent('projeto.pdf', 'conteudo'))
            ->toMediaCollection();
        $taskFile = $task
            ->addMedia(UploadedFile::fake()->createWithContent('tarefa.pdf', 'conteudo'))
            ->toMediaCollection();
        $meetingFile = $meeting
            ->addMedia(UploadedFile::fake()->createWithContent('reuniao.pdf', 'conteudo'))
            ->toMediaCollection();
        $meeting->sharedFiles()->attach($projectFile);
        $comment = Comment::query()->create([
            'user_id' => $user->id,
            'commentable_type' => $task->getMorphClass(),
            'commentable_id' => $task->id,
            'text' => 'Comentário do contexto',
            'is_active' => true,
        ]);

        $resolver = app(FileContextResolver::class);

        $this->assertSame(
            [$projectFile->uuid],
            $resolver->filesFor($project)->pluck('uuid')->all(),
        );
        $this->assertSame(
            [$taskFile->uuid, $projectFile->uuid],
            $resolver->filesFor($task)->pluck('uuid')->all(),
        );
        $this->assertSame(
            [$meetingFile->uuid, $projectFile->uuid],
            $resolver->filesFor($meeting)->pluck('uuid')->all(),
        );
        $this->assertSame(
            [$meetingFile->uuid, $projectFile->uuid],
            $resolver->filesFor($meetingItem)->pluck('uuid')->all(),
        );
        $this->assertSame(
            [$taskFile->uuid, $projectFile->uuid],
            $resolver->filesFor($comment)->pluck('uuid')->all(),
        );
    }

    public function test_meeting_context_prioritizes_its_shared_file_section(): void
    {
        Storage::fake('files');
        Queue::fake();

        $user = $this->user('Pessoa da reunião');
        $project = $this->projectWithMember('Projeto da reunião navegável', $user);
        $this->enableModule($project, 'meetings');
        $meeting = Meeting::query()->create([
            'title' => 'Reunião navegável',
            'status' => 'DRAFT',
        ]);
        $meeting->projects()->attach($project);

        $this->actingAs($user);
        $media = $project
            ->addMedia(UploadedFile::fake()->createWithContent('compartilhado.txt', 'conteudo'))
            ->toMediaCollection();
        DB::table('meeting_file_shares')->insert([
            'meeting_id' => $meeting->id,
            'media_id' => $media->id,
            'shared_by' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->get(route('files.show', [
            'uuid' => $media->uuid,
            'context_type' => 'meeting',
            'context_id' => $meeting->id,
            'context_project_id' => $project->id,
        ]))->assertRedirect(
            route('projects.meetings.show', [$project, $meeting]).'#file-'.$media->uuid
        );

        $this->getJson(route('files.navigation', [
            'uuid' => $media->uuid,
            'context_type' => 'meeting',
            'context_id' => $meeting->id,
            'context_project_id' => $project->id,
        ]))
            ->assertOk()
            ->assertJsonPath(
                'url',
                route('projects.meetings.show', [$project, $meeting]).'#file-'.$media->uuid
            )
            ->assertJsonPath('opens_new_tab', false);

        $meetingFile = $meeting
            ->addMedia(UploadedFile::fake()->createWithContent('reuniao.txt', 'conteudo'))
            ->toMediaCollection();

        $this->getJson(route('files.navigation', [
            'uuid' => $meetingFile->uuid,
            'context_type' => 'meeting',
            'context_id' => $meeting->id,
            'context_project_id' => $project->id,
        ]))
            ->assertOk()
            ->assertJsonPath(
                'url',
                route('projects.meetings.show', [$project, $meeting]).'#file-'.$meetingFile->uuid
            )
            ->assertJsonPath('opens_new_tab', false);
    }

    public function test_shared_only_access_falls_back_to_a_viewable_meeting(): void
    {
        Storage::fake('files');
        Queue::fake();

        $ownerUser = $this->user('Pessoa do projeto de origem');
        $viewer = $this->user('Pessoa da audiência');
        $ownerProject = $this->projectWithMember('Projeto de origem', $ownerUser);
        $meetingProject = $this->projectWithMember('Projeto da audiência navegável', $viewer, 'VIEWER');
        $this->enableModule($meetingProject, 'meetings');
        $meeting = Meeting::query()->create([
            'title' => 'Reunião compartilhada navegável',
            'status' => 'DRAFT',
        ]);
        $meeting->projects()->attach([$ownerProject->id, $meetingProject->id]);

        $this->actingAs($ownerUser);
        $media = $ownerProject
            ->addMedia(UploadedFile::fake()->createWithContent('restrito.txt', 'conteudo'))
            ->toMediaCollection();
        DB::table('meeting_file_shares')->insert([
            'meeting_id' => $meeting->id,
            'media_id' => $media->id,
            'shared_by' => $ownerUser->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($viewer)
            ->get(route('files.show', ['uuid' => $media->uuid]))
            ->assertRedirect(
                route('projects.meetings.show', [$meetingProject, $meeting])
                    .'#file-'.$media->uuid
            );
    }

    public function test_file_reference_navigates_to_the_page_that_contains_the_card(): void
    {
        Storage::fake('files');
        Queue::fake();

        $user = $this->user('Pessoa com muitos arquivos');
        $project = $this->projectWithMember('Projeto paginado', $user);
        $this->actingAs($user);
        $oldest = null;

        foreach (range(1, 21) as $number) {
            $media = $project
                ->addMedia(UploadedFile::fake()->createWithContent("arquivo-{$number}.txt", 'conteudo'))
                ->toMediaCollection();
            $oldest ??= $media;
        }

        $destination = route('projects.show', $project)
            .'?files_page=2#file-'.$oldest->uuid;

        $this->get(route('files.show', ['uuid' => $oldest->uuid]))
            ->assertRedirect($destination);
        $this->getJson(route('files.navigation', [
            'uuid' => $oldest->uuid,
            'context_type' => 'project',
            'context_id' => $project->id,
        ]))
            ->assertOk()
            ->assertExactJson([
                'url' => $destination,
                'opens_new_tab' => false,
            ]);
    }

    public function test_file_reference_hides_missing_and_inaccessible_files_as_not_found(): void
    {
        Storage::fake('files');
        Queue::fake();

        $owner = $this->user('Pessoa proprietária do contexto');
        $outsider = $this->user('Pessoa sem acesso ao contexto');
        $project = $this->projectWithMember('Projeto reservado', $owner);
        $this->actingAs($owner);
        $media = $project
            ->addMedia(UploadedFile::fake()->createWithContent('reservado.txt', 'conteudo'))
            ->toMediaCollection();

        $this->actingAs($outsider)
            ->get(route('files.show', ['uuid' => $media->uuid]))
            ->assertNotFound();
        $this->getJson(route('files.navigation', ['uuid' => $media->uuid]))
            ->assertNotFound();
        $this->get(route('files.show', [
            'uuid' => '11111111-1111-4111-8111-111111111111',
        ]))->assertNotFound();
    }

    public function test_mention_selector_returns_only_direct_members_and_saving_rejects_new_ineligible_mentions(): void
    {
        $editor = $this->user('Pessoa que edita');
        $directMember = $this->user('Pessoa diretamente vinculada');
        $inheritedMember = $this->user('Pessoa com acesso herdado');
        $outsider = $this->user('Pessoa de outro projeto');
        $project = $this->projectWithMember('Projeto com menções', $editor, 'ADMIN');
        $project->users()->attach($directMember, ['role' => 'VIEWER']);
        $parent = $this->projectWithMember('Projeto pai', $inheritedMember, 'VIEWER');
        $project->update(['parent_id' => $parent->id, 'permission_inheritance' => 'FULL']);

        $this->actingAs($editor)
            ->getJson(route('mentions.selectable', [
                'context_type' => 'project',
                'context_id' => $project->id,
                'term' => 'Pessoa',
            ]))
            ->assertOk()
            ->assertJsonFragment(['id' => $editor->id, 'name' => $editor->name])
            ->assertJsonFragment(['id' => $directMember->id, 'name' => $directMember->name])
            ->assertJsonMissing(['id' => $inheritedMember->id])
            ->assertJsonMissing(['id' => $outsider->id]);

        $allowed = '@[Pessoa diretamente vinculada](mention:user:' . $directMember->id . ')';
        $this->patch(route('projects.updateDescription', $project), ['description' => $allowed])
            ->assertRedirect();

        $this->assertDatabaseHas('mentions', [
            'source_type' => 'project',
            'source_id' => $project->id,
            'source_field' => 'description',
            'target_type' => 'user',
            'target_id' => $directMember->id,
        ]);

        $this->patch(route('projects.updateDescription', $project), [
            'description' => $allowed . ' @[Pessoa de outro projeto](mention:user:' . $outsider->id . ')',
        ])
            ->assertSessionHasErrors('description');

        $this->assertSame($allowed, $project->fresh()->description);
        $this->assertDatabaseCount('mentions', 1);
    }

    public function test_it_indexes_a_file_mention_by_public_uuid_and_resolves_the_internal_target_relation(): void
    {
        Storage::fake('files');
        Queue::fake();

        $author = $this->user('Pessoa autora do Arquivo');
        $project = $this->projectWithMember('Projeto do Arquivo mencionado', $author);

        $this->actingAs($author);
        $media = $project
            ->addMedia(UploadedFile::fake()->createWithContent('decisao.pdf', 'conteudo'))
            ->toMediaCollection();
        $markdown = '@[Rótulo histórico](mention:file:' . $media->uuid . ')';

        app(MentionManager::class)->synchronize($project, 'description', $markdown);

        $mention = Mention::query()->firstOrFail();

        $this->assertSame('file', $mention->target_type);
        $this->assertSame((string) $media->id, (string) $mention->target_id);
        $this->assertTrue($mention->target->is($media));
        $this->assertTrue($media->incomingMentions()->whereKey($mention->id)->exists());
        $this->assertTrue(
            app(MentionManager::class)->incomingMentions($media, $author)->contains('id', $mention->id),
        );
    }

    public function test_file_mentions_are_searchable_by_uuid_in_the_unified_autocomplete(): void
    {
        Storage::fake('files');
        Queue::fake();

        $author = $this->user('Pessoa que pesquisa Arquivos');
        $project = $this->projectWithMember('Projeto contextual de Arquivos', $author);
        $otherProject = $this->projectWithMember('Outro projeto de Arquivos', $author);

        $this->actingAs($author);
        $contextual = $project
            ->addMedia(UploadedFile::fake()->createWithContent('contexto.pdf', 'conteudo'))
            ->toMediaCollection();
        $other = $otherProject
            ->addMedia(UploadedFile::fake()->createWithContent('fora-do-contexto.pdf', 'conteudo'))
            ->toMediaCollection();

        $this->getJson(route('mentions.selectable', [
            'context_type' => 'project',
            'context_id' => $project->id,
            'filter' => 'file',
            'term' => 'contexto',
        ]))
            ->assertOk()
            ->assertJsonPath('results.0.id', $contextual->uuid)
            ->assertJsonPath('results.0.name', 'contexto')
            ->assertJsonPath('results.0.type', 'file')
            ->assertJsonPath('results.0.type_label', 'Arquivo')
            ->assertJsonMissing(['id' => $other->uuid])
            ->assertJsonFragment(['value' => 'file', 'label' => 'Arquivos']);
    }

    public function test_file_mentions_render_the_current_name_and_authorized_uuid_route_without_rewriting_markdown(): void
    {
        Storage::fake('files');
        Queue::fake();

        $reader = $this->user('Pessoa que lê Arquivos');
        $project = $this->projectWithMember('Projeto da apresentação do Arquivo', $reader);

        $this->actingAs($reader);
        $media = $project
            ->addMedia(UploadedFile::fake()->createWithContent('decisao.pdf', 'conteudo'))
            ->toMediaCollection();
        $markdown = '@[Nome histórico](mention:file:' . $media->uuid . ')';
        $project->update(['description' => $markdown]);
        app(MentionManager::class)->synchronize($project, 'description', $markdown);

        $media->display_name = 'decisao final';
        $media->save();

        $presentation = app(MentionManager::class)->present('file', $media->uuid, $reader);

        $this->assertSame('available', $presentation['status']);
        $this->assertSame('decisao final', $presentation['label']);
        $this->assertSame(
            route('files.show', ['uuid' => $media->uuid]),
            $presentation['url'],
        );
        $this->assertSame('arquivo: decisao final', $presentation['accessible_name']);
        $html = app(MarkdownRenderer::class)->render($markdown);
        $this->assertStringContainsString('decisao final', $html);
        $this->assertStringContainsString('aria-label="arquivo: decisao final"', $html);
        $this->assertStringContainsString('title="arquivo: decisao final"', $html);
        $this->assertSame($markdown, $project->fresh()->description);
    }

    public function test_new_file_mentions_hide_missing_and_out_of_context_files_behind_one_validation_message(): void
    {
        Storage::fake('files');
        Queue::fake();

        $author = $this->user('Pessoa que valida Arquivos');
        $project = $this->projectWithMember('Projeto do texto', $author);
        $otherProject = $this->projectWithMember('Projeto fora do texto', $author);

        $this->actingAs($author);
        $otherMedia = $otherProject
            ->addMedia(UploadedFile::fake()->createWithContent('fora.pdf', 'conteudo'))
            ->toMediaCollection();
        $manager = app(MentionManager::class);
        $messages = [];

        foreach ([$otherMedia->uuid, '11111111-1111-4111-8111-111111111111'] as $uuid) {
            try {
                $manager->validateAllMentions(
                    $project,
                    'description',
                    '@[Arquivo](mention:file:' . $uuid . ')',
                );
            } catch (ValidationException $exception) {
                $messages[] = $exception->errors()['description'][0];
            }
        }

        $this->assertSame([
            'Uma ou mais Menções não existem ou não são permitidas neste contexto.',
            'Uma ou mais Menções não existem ou não são permitidas neste contexto.',
        ], $messages);
    }

    public function test_definitive_file_deletion_removes_the_incoming_relation_without_rewriting_markdown(): void
    {
        Storage::fake('files');
        Queue::fake();

        $author = $this->user('Pessoa que remove Arquivos');
        $project = $this->projectWithMember('Projeto com Arquivo removível', $author);

        $this->actingAs($author);
        $media = $project
            ->addMedia(UploadedFile::fake()->createWithContent('remover.pdf', 'conteudo'))
            ->toMediaCollection();
        $markdown = '@[Arquivo removível](mention:file:' . $media->uuid . ')';
        $project->update(['description' => $markdown]);
        app(MentionManager::class)->synchronize($project, 'description', $markdown);

        $media->delete();

        $this->assertDatabaseMissing('mentions', [
            'target_type' => 'file',
            'target_id' => $media->id,
        ]);
        $this->assertSame($markdown, $project->fresh()->description);
    }

    public function test_repeated_file_mentions_are_deduplicated_without_changing_the_markdown(): void
    {
        Storage::fake('files');
        Queue::fake();

        $author = $this->user('Pessoa que repete Arquivos');
        $project = $this->projectWithMember('Projeto com Arquivo repetido', $author);

        $this->actingAs($author);
        $media = $project
            ->addMedia(UploadedFile::fake()->createWithContent('repetido.pdf', 'conteudo'))
            ->toMediaCollection();
        $markdown = implode(' ', [
            '@[Arquivo](mention:file:' . $media->uuid . ')',
            '@[Arquivo outra vez](mention:file:' . $media->uuid . ')',
        ]);
        $project->update(['description' => $markdown]);

        app(MentionManager::class)->synchronize($project, 'description', $markdown);

        $this->assertDatabaseCount('mentions', 1);
        $this->assertSame($markdown, $project->fresh()->description);
    }

    public function test_common_file_links_remain_links_and_stay_out_of_the_mentions_index(): void
    {
        Storage::fake('files');
        Queue::fake();

        $author = $this->user('Pessoa com link antigo');
        $project = $this->projectWithMember('Projeto com link antigo', $author);

        $this->actingAs($author);
        $media = $project
            ->addMedia(UploadedFile::fake()->createWithContent('legado.pdf', 'conteudo'))
            ->toMediaCollection();
        $markdown = '[Arquivo legado](/files/' . $media->uuid . ')';
        $project->update(['description' => $markdown]);

        app(MentionManager::class)->synchronize($project, 'description', $markdown);

        $this->assertDatabaseCount('mentions', 0);
        $this->assertStringContainsString('/files/' . $media->uuid, app(MarkdownRenderer::class)->render($markdown));
    }

    public function test_file_presentation_distinguishes_missing_owner_from_an_unauthorized_reader_without_exposing_history(): void
    {
        Storage::fake('files');
        Queue::fake();

        $owner = $this->user('Pessoa proprietária do Arquivo');
        $reader = $this->user('Pessoa sem acesso ao Arquivo');
        $project = $this->projectWithMember('Projeto do Arquivo restrito', $owner);

        $this->actingAs($owner);
        $media = $project
            ->addMedia(UploadedFile::fake()->createWithContent('restrito.pdf', 'conteudo'))
            ->toMediaCollection();
        $markdown = '@[Nome secreto](mention:file:' . $media->uuid . ')';
        $project->update(['description' => $markdown]);
        app(MentionManager::class)->synchronize($project, 'description', $markdown);

        $this->actingAs($reader);
        $this->assertSame([
            'status' => 'forbidden',
            'type' => 'arquivo',
            'message' => 'Menção a arquivo: você não tem permissão para visualizar',
        ], app(MentionManager::class)->present('file', $media->uuid, $reader));
        $this->assertStringNotContainsString(
            'Nome secreto',
            app(MarkdownRenderer::class)->render($markdown),
        );

        $project->delete();

        $this->assertSame('missing', app(MentionManager::class)->present('file', $media->uuid, $owner)['status']);
    }

    public function test_meeting_file_mentions_require_explicit_sharing_and_keep_that_share_after_text_removal(): void
    {
        Storage::fake('files');
        Queue::fake();

        $editor = $this->user('Pessoa que edita a Reunião');
        $project = $this->projectWithMember('Projeto da Reunião do Arquivo', $editor, 'CONTRIBUTOR');
        $this->enableModule($project, 'meetings');
        $meeting = Meeting::query()->create([
            'title' => 'Reunião com Arquivo',
            'status' => 'DRAFT',
        ]);
        $meeting->projects()->attach($project);

        $this->actingAs($editor);
        $media = $project
            ->addMedia(UploadedFile::fake()->createWithContent('pauta.pdf', 'conteudo'))
            ->toMediaCollection();
        $markdown = '@[Pauta](mention:file:' . $media->uuid . ')';
        $manager = app(MentionManager::class);

        try {
            $manager->validateAllMentions($meeting, 'notes', $markdown, $editor);
            $this->fail('Um Arquivo não compartilhado não deveria ser elegível na Reunião.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'Uma ou mais Menções não existem ou não são permitidas neste contexto.',
                $exception->errors()['notes'][0],
            );
        }

        $this->postJson(route('meetings.file-shares.store', $meeting), [
            'media_uuid' => $media->uuid,
        ])->assertCreated();

        $meeting->notes = $markdown;
        $manager->validateAllMentions($meeting, 'notes', $markdown, $editor);
        $manager->synchronize($meeting, 'notes', $markdown, true, $editor);
        $this->assertDatabaseHas('mentions', [
            'source_type' => 'meeting',
            'source_id' => $meeting->id,
            'target_type' => 'file',
            'target_id' => $media->id,
        ]);

        $meeting->notes = null;
        $manager->synchronize($meeting, 'notes', null, true, $editor);

        $this->assertDatabaseHas('meeting_file_shares', [
            'meeting_id' => $meeting->id,
            'media_id' => $media->id,
        ]);
        $this->assertDatabaseMissing('mentions', [
            'source_type' => 'meeting',
            'source_id' => $meeting->id,
            'target_type' => 'file',
            'target_id' => $media->id,
        ]);
    }

    public function test_task_file_selector_returns_its_own_files_and_files_from_its_project_without_inheriting_other_projects(): void
    {
        Storage::fake('files');
        Queue::fake();

        $user = $this->user('Pessoa colaboradora');
        $project = $this->projectWithMember('Projeto da tarefa', $user);
        $otherProject = $this->projectWithMember('Projeto sem vínculo', $user);
        $this->enableModule($project, 'tasks');

        $task = Task::query()->create([
            'project_id' => $project->id,
            'title' => 'Implementar referência',
            'status' => 'NEW',
        ]);

        $this->actingAs($user);
        $projectMedia = $project
            ->addMedia(UploadedFile::fake()->createWithContent('projeto.pdf', 'conteudo'))
            ->toMediaCollection();
        $taskMedia = $task
            ->addMedia(UploadedFile::fake()->createWithContent('tarefa.pdf', 'conteudo'))
            ->toMediaCollection();
        $otherMedia = $otherProject
            ->addMedia(UploadedFile::fake()->createWithContent('outro.pdf', 'conteudo'))
            ->toMediaCollection();

        $this->getJson(route('files.selectable', [
            'context_type' => 'task',
            'context_id' => $task->id,
        ]))
            ->assertOk()
            ->assertJsonPath('result_groups.0.label', 'Tarefa atual: '.$task->title)
            ->assertJsonPath('result_groups.0.results.0.uuid', $taskMedia->uuid)
            ->assertJsonPath('result_groups.1.label', 'Projeto da tarefa: '.$project->name)
            ->assertJsonPath('result_groups.1.results.0.uuid', $projectMedia->uuid)
            ->assertJsonFragment(['uuid' => $projectMedia->uuid])
            ->assertJsonFragment(['uuid' => $taskMedia->uuid])
            ->assertJsonMissing(['uuid' => $otherMedia->uuid]);
    }

    public function test_meeting_media_card_lists_shareable_files_in_the_existing_share_modal(): void
    {
        Storage::fake('files');
        Queue::fake();

        $editor = $this->user('Pessoa que edita a reunião');
        $project = $this->projectWithMember('Projeto da reunião', $editor, 'CONTRIBUTOR');
        $this->enableModule($project, 'meetings');
        $meeting = Meeting::query()->create([
            'title' => 'Reunião com arquivos compartilháveis',
            'status' => 'DRAFT',
        ]);
        $meeting->projects()->attach($project);

        $this->actingAs($editor);
        $media = $project
            ->addMedia(UploadedFile::fake()->createWithContent('material-da-pauta.pdf', 'conteudo'))
            ->toMediaCollection();

        $this->get(route('projects.meetings.show', [$project, $meeting]))
            ->assertOk()
            ->assertSee('Compartilhar links e arquivos')
            ->assertSee('Compartilhar links e arquivos com a reunião')
            ->assertSee('material-da-pauta')
            ->assertSee('name="media_uuid" value="'.$media->uuid.'"', false)
            ->assertSee(route('meetings.file-shares.store', $meeting), false);
    }

    public function test_meeting_file_share_form_redirects_back_after_sharing_a_file(): void
    {
        Storage::fake('files');
        Queue::fake();

        $editor = $this->user('Pessoa que edita a reunião');
        $project = $this->projectWithMember('Projeto da reunião', $editor, 'CONTRIBUTOR');
        $this->enableModule($project, 'meetings');
        $meeting = Meeting::query()->create([
            'title' => 'Reunião com arquivo',
            'status' => 'DRAFT',
        ]);
        $meeting->projects()->attach($project);

        $this->actingAs($editor);
        $media = $project
            ->addMedia(UploadedFile::fake()->createWithContent('material-da-pauta.pdf', 'conteudo'))
            ->toMediaCollection();
        $meetingPage = route('projects.meetings.show', [$project, $meeting]);

        $this->from($meetingPage)
            ->post(route('meetings.file-shares.store', $meeting), ['media_uuid' => $media->uuid])
            ->assertRedirect($meetingPage);

        $this->assertDatabaseHas('meeting_file_shares', [
            'meeting_id' => $meeting->id,
            'media_id' => $media->id,
            'shared_by' => $editor->id,
        ]);
    }

    public function test_sharing_a_related_file_grants_meeting_viewers_read_access_without_transferring_ownership(): void
    {
        Storage::fake('files');
        Queue::fake();

        $editor = $this->user('Pessoa que edita a reunião');
        $meetingViewer = $this->user('Pessoa que visualiza a reunião');
        $sourceProject = $this->projectWithMember('Projeto de origem', $editor, 'CONTRIBUTOR');
        $viewerProject = $this->projectWithMember('Projeto da pessoa visualizadora', $meetingViewer);
        $this->enableModule($sourceProject, 'meetings');
        $this->enableModule($viewerProject, 'meetings');

        $meeting = Meeting::query()->create([
            'title' => 'Reunião multiprojeto',
            'status' => 'DRAFT',
        ]);
        $meeting->projects()->attach([$sourceProject->id, $viewerProject->id]);

        $this->actingAs($editor);
        $media = $sourceProject
            ->addMedia(UploadedFile::fake()->createWithContent('contexto.pdf', 'conteudo'))
            ->toMediaCollection();

        $this->postJson(route('meetings.file-shares.store', $meeting), [
            'media_uuid' => $media->uuid,
        ])
            ->assertCreated()
            ->assertJsonPath('uuid', $media->uuid);

        $this->assertDatabaseHas('meeting_file_shares', [
            'meeting_id' => $meeting->id,
            'media_id' => $media->id,
            'shared_by' => $editor->id,
        ]);
        $this->assertSame($sourceProject->id, $media->fresh()->model_id);

        $this->actingAs($meetingViewer)
            ->getJson(route('files.metadata', ['uuid' => $media->uuid]))
            ->assertOk()
            ->assertJsonPath('uuid', $media->uuid);
    }

    public function test_meeting_contributor_can_remove_a_share_without_deleting_the_original_or_rewriting_references(): void
    {
        Storage::fake('files');
        Queue::fake();

        $sourceEditor = $this->user('Pessoa da origem');
        $meetingEditor = $this->user('Pessoa que remove da reunião');
        $sourceProject = $this->projectWithMember('Projeto de origem', $sourceEditor, 'CONTRIBUTOR');
        $meetingProject = $this->projectWithMember('Projeto da reunião', $meetingEditor, 'CONTRIBUTOR');
        $this->enableModule($sourceProject, 'meetings');
        $this->enableModule($meetingProject, 'meetings');

        $meeting = Meeting::query()->create([
            'title' => 'Reunião compartilhada',
            'status' => 'DRAFT',
        ]);
        $meeting->projects()->attach([$sourceProject->id, $meetingProject->id]);

        $this->actingAs($sourceEditor);
        $media = $sourceProject
            ->addMedia(UploadedFile::fake()->createWithContent('decisao.pdf', 'conteudo'))
            ->toMediaCollection();
        $this->postJson(route('meetings.file-shares.store', $meeting), ['media_uuid' => $media->uuid])
            ->assertCreated();

        $this->actingAs($meetingEditor)
            ->deleteJson(route('meetings.file-shares.destroy', [$meeting, $media->uuid]))
            ->assertNoContent();

        $this->assertDatabaseMissing('meeting_file_shares', [
            'meeting_id' => $meeting->id,
            'media_id' => $media->id,
        ]);
        $this->assertDatabaseHas('media', ['id' => $media->id]);

        $this->actingAs($meetingEditor)
            ->getJson(route('files.metadata', ['uuid' => $media->uuid]))
            ->assertNotFound();

        $this->actingAs($sourceEditor)
            ->getJson(route('files.metadata', ['uuid' => $media->uuid]))
            ->assertOk();
    }

    public function test_removing_a_share_from_the_meeting_page_redirects_back_to_it(): void
    {
        Storage::fake('files');
        Queue::fake();

        $editor = $this->user('Pessoa que remove da reunião');
        $project = $this->projectWithMember('Projeto da reunião', $editor, 'CONTRIBUTOR');
        $this->enableModule($project, 'meetings');
        $meeting = Meeting::query()->create([
            'title' => 'Reunião compartilhada',
            'status' => 'DRAFT',
        ]);
        $meeting->projects()->attach($project);

        $this->actingAs($editor);
        $media = $project
            ->addMedia(UploadedFile::fake()->createWithContent('decisao.pdf', 'conteudo'))
            ->toMediaCollection();
        $this->postJson(route('meetings.file-shares.store', $meeting), ['media_uuid' => $media->uuid])
            ->assertCreated();

        $meetingPage = route('projects.meetings.show', [$project, $meeting]);

        $this->from($meetingPage)
            ->delete(route('meetings.file-shares.destroy', [$meeting, $media->uuid]))
            ->assertRedirect($meetingPage);
    }

    public function test_share_persists_while_a_soft_deleted_source_temporarily_hides_the_file_and_restoration_recovers_access(): void
    {
        Storage::fake('files');
        Queue::fake();

        $sourceEditor = $this->user('Pessoa da origem');
        $meetingViewer = $this->user('Pessoa da reunião');
        $sourceProject = $this->projectWithMember('Projeto de origem', $sourceEditor, 'CONTRIBUTOR');
        $meetingProject = $this->projectWithMember('Projeto da reunião', $meetingViewer, 'VIEWER');
        $this->enableModule($sourceProject, 'meetings');
        $this->enableModule($meetingProject, 'meetings');
        $meeting = Meeting::query()->create(['title' => 'Reunião compartilhada', 'status' => 'DRAFT']);
        $meeting->projects()->attach([$sourceProject->id, $meetingProject->id]);

        $this->actingAs($sourceEditor);
        $media = $sourceProject
            ->addMedia(UploadedFile::fake()->createWithContent('registro.pdf', 'conteudo'))
            ->toMediaCollection();
        $this->postJson(route('meetings.file-shares.store', $meeting), ['media_uuid' => $media->uuid])
            ->assertCreated();

        $sourceProject->delete();

        $this->assertDatabaseHas('meeting_file_shares', [
            'meeting_id' => $meeting->id,
            'media_id' => $media->id,
        ]);
        $this->actingAs($meetingViewer)
            ->getJson(route('files.metadata', ['uuid' => $media->uuid]))
            ->assertNotFound();

        $sourceProject->restore();

        $this->actingAs($meetingViewer)
            ->getJson(route('files.metadata', ['uuid' => $media->uuid]))
            ->assertOk();
    }

    public function test_meeting_file_selector_returns_only_its_own_and_explicitly_shared_files(): void
    {
        Storage::fake('files');
        Queue::fake();

        $sourceEditor = $this->user('Pessoa da origem');
        $meetingEditor = $this->user('Pessoa da reunião');
        $sourceProject = $this->projectWithMember('Projeto de origem', $sourceEditor, 'CONTRIBUTOR');
        $meetingProject = $this->projectWithMember('Projeto da reunião', $meetingEditor, 'CONTRIBUTOR');
        $this->enableModule($sourceProject, 'meetings');
        $this->enableModule($meetingProject, 'meetings');

        $meeting = Meeting::query()->create([
            'title' => 'Reunião com Arquivos',
            'status' => 'DRAFT',
        ]);
        $meeting->projects()->attach([$sourceProject->id, $meetingProject->id]);

        $this->actingAs($sourceEditor);
        $shared = $sourceProject
            ->addMedia(UploadedFile::fake()->createWithContent('compartilhado.pdf', 'conteudo'))
            ->toMediaCollection();
        $unshared = $sourceProject
            ->addMedia(UploadedFile::fake()->createWithContent('nao-compartilhado.pdf', 'conteudo'))
            ->toMediaCollection();
        $this->postJson(route('meetings.file-shares.store', $meeting), ['media_uuid' => $shared->uuid])
            ->assertCreated();

        $this->actingAs($meetingEditor);
        $own = $meeting
            ->addMedia(UploadedFile::fake()->createWithContent('registro.pdf', 'conteudo'))
            ->toMediaCollection();

        $this->getJson(route('files.selectable', [
            'context_type' => 'meeting',
            'context_id' => $meeting->id,
        ]))
            ->assertOk()
            ->assertJsonFragment(['uuid' => $own->uuid])
            ->assertJsonFragment(['uuid' => $shared->uuid])
            ->assertJsonMissing(['uuid' => $unshared->uuid]);

        $this->actingAs($sourceEditor)
            ->getJson(route('files.selectable', [
                'context_type' => 'meeting',
                'context_id' => $meeting->id,
        ]))
            ->assertOk()
            ->assertJsonFragment(['uuid' => $shared->uuid])
            ->assertJsonPath('shareable_results.0.uuid', $unshared->uuid)
            ->assertJsonCount(1, 'shareable_results');
    }

    public function test_comment_file_selector_uses_the_commented_task_context(): void
    {
        Storage::fake('files');
        Queue::fake();

        $user = $this->user('Pessoa que comenta');
        $project = $this->projectWithMember('Projeto comentado', $user);
        $otherProject = $this->projectWithMember('Projeto não comentado', $user);
        $this->enableModule($project, 'tasks');
        $task = Task::query()->create([
            'project_id' => $project->id,
            'title' => 'Tarefa comentada',
            'status' => 'NEW',
        ]);

        $this->actingAs($user);
        $projectMedia = $project
            ->addMedia(UploadedFile::fake()->createWithContent('projeto.pdf', 'conteudo'))
            ->toMediaCollection();
        $taskMedia = $task
            ->addMedia(UploadedFile::fake()->createWithContent('tarefa.pdf', 'conteudo'))
            ->toMediaCollection();
        $otherMedia = $otherProject
            ->addMedia(UploadedFile::fake()->createWithContent('outro.pdf', 'conteudo'))
            ->toMediaCollection();

        $this->getJson(route('files.selectable', [
            'context_type' => 'comment',
            'commentable_type' => 'task',
            'commentable_id' => $task->id,
        ]))
            ->assertOk()
            ->assertJsonFragment(['uuid' => $projectMedia->uuid])
            ->assertJsonFragment(['uuid' => $taskMedia->uuid])
            ->assertJsonMissing(['uuid' => $otherMedia->uuid]);
    }

    public function test_file_of_a_task_in_the_agenda_can_be_shared_with_the_meeting_regardless_of_task_status(): void
    {
        Storage::fake('files');
        Queue::fake();

        $editor = $this->user('Pessoa que edita a reunião');
        $viewer = $this->user('Pessoa que visualiza a reunião');
        $project = $this->projectWithMember('Projeto da pauta', $editor, 'CONTRIBUTOR');
        $viewerProject = $this->projectWithMember('Projeto da audiência', $viewer, 'VIEWER');
        $this->enableModule($project, 'meetings');
        $this->enableModule($viewerProject, 'meetings');

        $task = Task::query()->create([
            'project_id' => $project->id,
            'title' => 'Tarefa concluída da pauta',
            'status' => 'DONE',
        ]);
        $meeting = Meeting::query()->create([
            'title' => 'Reunião da pauta',
            'status' => 'DRAFT',
        ]);
        $meeting->projects()->attach([$project->id, $viewerProject->id]);
        $meetingItem = MeetingItem::query()->create([
            'meeting_id' => $meeting->id,
            'discussable_type' => $task->getMorphClass(),
            'discussable_id' => $task->id,
            'order' => 1,
        ]);

        $this->actingAs($editor);
        $media = $task
            ->addMedia(UploadedFile::fake()->createWithContent('historico.pdf', 'conteudo'))
            ->toMediaCollection();

        $this->postJson(route('meetings.file-shares.store', $meeting), [
            'media_uuid' => $media->uuid,
        ])
            ->assertCreated()
            ->assertJsonPath('markdown', "@[historico](mention:file:{$media->uuid})");

        $this->assertDatabaseHas('meeting_file_shares', [
            'meeting_id' => $meeting->id,
            'media_id' => $media->id,
        ]);

        $meetingItem->delete();

        $this->assertDatabaseHas('meeting_file_shares', [
            'meeting_id' => $meeting->id,
            'media_id' => $media->id,
        ]);
        $this->actingAs($viewer)
            ->getJson(route('files.metadata', ['uuid' => $media->uuid]))
            ->assertOk();
    }

    public function test_file_of_a_project_in_the_agenda_can_be_shared_with_the_meeting(): void
    {
        Storage::fake('files');
        Queue::fake();

        $editor = $this->user('Pessoa que edita a reunião');
        $meetingProject = $this->projectWithMember('Projeto vinculado à reunião', $editor, 'CONTRIBUTOR');
        $agendaProject = $this->projectWithMember('Projeto presente na pauta', $editor, 'CONTRIBUTOR');
        $this->enableModule($meetingProject, 'meetings');

        $meeting = Meeting::query()->create([
            'title' => 'Reunião da pauta',
            'status' => 'DRAFT',
        ]);
        $meeting->projects()->attach($meetingProject);
        MeetingItem::query()->create([
            'meeting_id' => $meeting->id,
            'discussable_type' => $agendaProject->getMorphClass(),
            'discussable_id' => $agendaProject->id,
            'order' => 1,
        ]);

        $this->actingAs($editor);
        $media = $agendaProject
            ->addMedia(UploadedFile::fake()->createWithContent('contexto-da-pauta.pdf', 'conteudo'))
            ->toMediaCollection();

        $shareRoute = route('meetings.file-shares.store', $meeting);
        config(['app.url' => 'http://localhost/gestao-projetos/public']);
        URL::forceRootUrl('http://localhost/gestao-projetos/public');

        $this->postJson($shareRoute, [
            'media_uuid' => $media->uuid,
        ])
            ->assertCreated()
            ->assertJsonPath('markdown', "@[contexto-da-pauta](mention:file:{$media->uuid})");

        $this->assertDatabaseHas('meeting_file_shares', [
            'meeting_id' => $meeting->id,
            'media_id' => $media->id,
        ]);
    }

    public function test_meeting_item_selector_lists_files_of_a_project_in_the_agenda_as_shareable(): void
    {
        Storage::fake('files');
        Queue::fake();

        $editor = $this->user('Pessoa que edita a reunião');
        $meetingProject = $this->projectWithMember('Projeto vinculado à reunião', $editor, 'CONTRIBUTOR');
        $agendaProject = $this->projectWithMember('Projeto presente na pauta', $editor, 'CONTRIBUTOR');
        $this->enableModule($meetingProject, 'meetings');

        $meeting = Meeting::query()->create([
            'title' => 'Reunião da pauta',
            'status' => 'DRAFT',
        ]);
        $meeting->projects()->attach($meetingProject);
        $item = MeetingItem::query()->create([
            'meeting_id' => $meeting->id,
            'discussable_type' => $agendaProject->getMorphClass(),
            'discussable_id' => $agendaProject->id,
            'order' => 1,
        ]);

        $this->actingAs($editor);
        $media = $agendaProject
            ->addMedia(UploadedFile::fake()->createWithContent('contexto-da-pauta.pdf', 'conteudo'))
            ->toMediaCollection();

        $this->getJson(route('files.selectable', [
            'context_type' => 'meeting_item',
            'context_id' => $item->id,
        ]))
            ->assertOk()
            ->assertJsonPath('shareable_results.0.uuid', $media->uuid)
            ->assertJsonPath('shareable_groups.0.label', 'Projeto na pauta: Projeto presente na pauta')
            ->assertJsonPath('shareable_groups.0.results.0.uuid', $media->uuid);
    }

    public function test_meeting_selector_groups_shareable_files_by_their_source(): void
    {
        Storage::fake('files');
        Queue::fake();

        $editor = $this->user('Pessoa que edita a reunião');
        $linkedProject = $this->projectWithMember('Projeto vinculado', $editor, 'CONTRIBUTOR');
        $agendaProject = $this->projectWithMember('Projeto da pauta', $editor, 'CONTRIBUTOR');
        $this->enableModule($linkedProject, 'meetings');
        $this->enableModule($agendaProject, 'tasks');

        $agendaTask = Task::query()->create([
            'project_id' => $agendaProject->id,
            'title' => 'Tarefa da pauta',
            'status' => 'NEW',
        ]);
        $meeting = Meeting::query()->create([
            'title' => 'Reunião com fontes',
            'status' => 'DRAFT',
        ]);
        $meeting->projects()->attach($linkedProject);
        MeetingItem::query()->create([
            'meeting_id' => $meeting->id,
            'discussable_type' => $agendaProject->getMorphClass(),
            'discussable_id' => $agendaProject->id,
            'order' => 1,
        ]);
        MeetingItem::query()->create([
            'meeting_id' => $meeting->id,
            'discussable_type' => $agendaTask->getMorphClass(),
            'discussable_id' => $agendaTask->id,
            'order' => 2,
        ]);

        $this->actingAs($editor);
        $linkedMedia = $linkedProject
            ->addMedia(UploadedFile::fake()->createWithContent('vinculado.pdf', 'conteudo'))
            ->toMediaCollection();
        $agendaProjectMedia = $agendaProject
            ->addMedia(UploadedFile::fake()->createWithContent('projeto-pauta.pdf', 'conteudo'))
            ->toMediaCollection();
        $agendaTaskMedia = $agendaTask
            ->addMedia(UploadedFile::fake()->createWithContent('tarefa-pauta.pdf', 'conteudo'))
            ->toMediaCollection();

        $this->getJson(route('files.selectable', [
            'context_type' => 'meeting',
            'context_id' => $meeting->id,
        ]))
            ->assertOk()
            ->assertJsonPath('shareable_groups.0.label', 'Projeto vinculado: Projeto vinculado')
            ->assertJsonPath('shareable_groups.0.results.0.uuid', $linkedMedia->uuid)
            ->assertJsonPath('shareable_groups.1.label', 'Projeto na pauta: Projeto da pauta')
            ->assertJsonPath('shareable_groups.1.results.0.uuid', $agendaProjectMedia->uuid)
            ->assertJsonPath('shareable_groups.2.label', 'Tarefa na pauta: Tarefa da pauta')
            ->assertJsonPath('shareable_groups.2.results.0.uuid', $agendaTaskMedia->uuid);
    }

    public function test_meeting_item_file_selector_uses_the_meeting_own_and_shared_files(): void
    {
        Storage::fake('files');
        Queue::fake();

        $sourceEditor = $this->user('Pessoa da origem');
        $meetingEditor = $this->user('Pessoa da pauta');
        $sourceProject = $this->projectWithMember('Projeto de origem', $sourceEditor, 'CONTRIBUTOR');
        $meetingProject = $this->projectWithMember('Projeto da pauta', $meetingEditor, 'CONTRIBUTOR');
        $this->enableModule($sourceProject, 'meetings');
        $this->enableModule($meetingProject, 'meetings');
        $meeting = Meeting::query()->create(['title' => 'Reunião da pauta', 'status' => 'DRAFT']);
        $meeting->projects()->attach([$sourceProject->id, $meetingProject->id]);
        $item = MeetingItem::query()->create([
            'meeting_id' => $meeting->id,
            'title' => 'Assunto independente',
            'order' => 1,
        ]);

        $this->actingAs($sourceEditor);
        $shared = $sourceProject
            ->addMedia(UploadedFile::fake()->createWithContent('compartilhado.pdf', 'conteudo'))
            ->toMediaCollection();
        $this->postJson(route('meetings.file-shares.store', $meeting), ['media_uuid' => $shared->uuid])
            ->assertCreated();

        $this->actingAs($meetingEditor);
        $own = $meeting
            ->addMedia(UploadedFile::fake()->createWithContent('registro.pdf', 'conteudo'))
            ->toMediaCollection();

        $this->getJson(route('files.selectable', [
            'context_type' => 'meeting_item',
            'context_id' => $item->id,
        ]))
            ->assertOk()
            ->assertJsonFragment(['uuid' => $own->uuid])
            ->assertJsonFragment(['uuid' => $shared->uuid]);
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->timestamps();
        });
        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status');
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable();
            $table->string('permission_inheritance')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->foreignId('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('project_user', function (Blueprint $table): void {
            $table->foreignId('project_id');
            $table->foreignId('user_id');
            $table->string('role');
            $table->boolean('pinned')->default(false);
            $table->timestamps();
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
            $table->string('status');
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->foreignId('deleted_by')->nullable();
            $table->boolean('deleted_via_project')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('meetings', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('status');
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->foreignId('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('meeting_projects', function (Blueprint $table): void {
            $table->foreignId('meeting_id');
            $table->foreignId('project_id');
        });
        Schema::create('meeting_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('meeting_id');
            $table->nullableMorphs('discussable');
            $table->string('title')->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        Schema::create('comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->morphs('commentable');
            $table->foreignId('parent_id')->nullable();
            $table->text('text');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('tags', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->string('type')->nullable();
            $table->unsignedInteger('order_column')->nullable();
            $table->timestamps();
        });
        Schema::create('taggables', function (Blueprint $table): void {
            $table->foreignId('tag_id');
            $table->morphs('taggable');
            $table->string('type')->nullable();
            $table->timestamps();
        });
        Schema::create('activity_log', function (Blueprint $table): void {
            $table->id();
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->nullableMorphs('subject');
            $table->nullableMorphs('causer');
            $table->json('properties')->nullable();
            $table->string('event')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->timestamps();
        });
        Schema::create('permissions', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });
        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });
        Schema::create('model_has_permissions', function (Blueprint $table): void {
            $table->foreignId('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });
        Schema::create('model_has_roles', function (Blueprint $table): void {
            $table->foreignId('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });
        Schema::create('role_has_permissions', function (Blueprint $table): void {
            $table->foreignId('permission_id');
            $table->foreignId('role_id');
        });

        (require database_path('migrations/2026_07_21_090000_create_media_table.php'))->up();
        (require database_path('migrations/2026_07_22_090000_create_meeting_file_shares_table.php'))->up();
        (require database_path('migrations/2026_07_23_090000_create_mentions_table.php'))->up();

        DB::table('permissions')->insert(collect([
            'admin', 'boss', 'manager', 'poweruser', 'user',
        ])->map(fn (string $name) => [
            'name' => $name,
            'guard_name' => 'senhaunica',
        ])->all());
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function user(string $name): User
    {
        return User::query()->create(['name' => $name]);
    }

    private function projectWithMember(string $name, User $user, string $role = 'ADMIN'): Project
    {
        $project = new Project();
        $project->forceFill([
            'name' => $name,
            'slug' => str($name)->slug(),
            'status' => 'ACTIVE',
        ])->save();
        $project->users()->attach($user, ['role' => $role]);

        return $project;
    }

    private function enableModule(Project $project, string $slug): void
    {
        $moduleId = DB::table('modules')->where('slug', $slug)->value('id');

        if (! $moduleId) {
            $moduleId = DB::table('modules')->insertGetId([
                'name' => ucfirst($slug),
                'slug' => $slug,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('project_modules')->insert([
            'project_id' => $project->id,
            'module_id' => $moduleId,
            'enabled' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
