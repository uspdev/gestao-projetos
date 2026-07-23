<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Meeting;
use App\Models\MeetingItem;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
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
        URL::forceRootUrl('http://localhost');

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

    public function test_markdown_file_reference_url_downloads_the_authorized_file_by_uuid(): void
    {
        Storage::fake('files');
        Queue::fake();

        $user = $this->user('Pessoa autorizada');
        $project = $this->projectWithMember('Projeto com referência', $user);

        $this->actingAs($user);
        $media = $project
            ->addMedia(UploadedFile::fake()->createWithContent('registro.pdf', 'conteudo'))
            ->toMediaCollection();

        $this->get("/files/{$media->uuid}")
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
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
            ->assertJsonFragment(['uuid' => $projectMedia->uuid])
            ->assertJsonFragment(['uuid' => $taskMedia->uuid])
            ->assertJsonMissing(['uuid' => $otherMedia->uuid]);
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
        $project = $this->projectWithMember('Projeto da pauta', $editor, 'CONTRIBUTOR');
        $this->enableModule($project, 'meetings');

        $task = Task::query()->create([
            'project_id' => $project->id,
            'title' => 'Tarefa concluída da pauta',
            'status' => 'DONE',
        ]);
        $meeting = Meeting::query()->create([
            'title' => 'Reunião da pauta',
            'status' => 'DRAFT',
        ]);
        $meeting->projects()->attach($project);
        MeetingItem::query()->create([
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
            ->assertJsonPath('markdown', "[historico](/files/{$media->uuid})");

        $this->assertDatabaseHas('meeting_file_shares', [
            'meeting_id' => $meeting->id,
            'media_id' => $media->id,
        ]);
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

        $this->postJson(route('meetings.file-shares.store', $meeting), [
            'media_uuid' => $media->uuid,
        ])
            ->assertCreated()
            ->assertJsonPath('markdown', "[contexto-da-pauta](/files/{$media->uuid})");

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
