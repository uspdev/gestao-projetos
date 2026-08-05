<?php

namespace Tests\Feature\Mentions;

use App\Models\Comment;
use App\Models\Meeting;
use App\Models\MeetingItem;
use App\Models\Mention;
use App\Models\Module;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\Mentions\MentionManager;
use App\Services\MarkdownRenderer;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MeetingMentionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

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
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->foreignId('deleted_by')->nullable();
            $table->foreignId('parent_id')->nullable();
            $table->string('permission_inheritance')->nullable();
            $table->foreignId('project_type_id')->nullable();
            $table->string('visibility')->nullable();
            $table->foreignId('phase_id')->nullable();
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
        Schema::create('role_has_permissions', function (Blueprint $table): void {
            $table->foreignId('permission_id');
            $table->foreignId('role_id');
        });
        Schema::create('model_has_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });
        Schema::create('model_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });
        DB::table('permissions')->insert([
            'name' => 'admin',
            'guard_name' => 'senhaunica',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('modules')->insert([
            [
                'name' => 'Reuniões',
                'slug' => 'meetings',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Tarefas',
                'slug' => 'tasks',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        Schema::create('tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id');
            $table->string('title');
            $table->text('description')->nullable();
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
            $table->unsignedInteger('order');
            $table->string('title')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
        Schema::create('media', function (Blueprint $table): void {
            $table->id();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->uuid('uuid')->unique();
            $table->string('collection_name');
            $table->string('name');
            $table->string('original_name');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->string('disk');
            $table->string('conversions_disk')->nullable();
            $table->unsignedBigInteger('size');
            $table->json('manipulations');
            $table->json('custom_properties');
            $table->json('generated_conversions');
            $table->json('responsive_images');
            $table->unsignedInteger('order_column')->nullable();
            $table->foreignId('uploaded_by')->nullable();
            $table->nullableTimestamps();
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

        (require database_path('migrations/2026_07_23_090000_create_mentions_table.php'))->up();
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');

        parent::tearDown();
    }

    public function test_it_indexes_a_meeting_mention_using_only_the_meeting_identity(): void
    {
        $author = User::query()->create(['name' => 'Pessoa autora']);
        $sourceProject = $this->project('Projeto fonte', $author, 'ADMIN');
        $targetProject = $this->project('Projeto destino', $author, 'VIEWER');
        $targetMeeting = $this->meeting('Reunião destino', $targetProject);
        $markdown = '@[Título histórico](mention:meeting:' . $targetMeeting->id . ')';

        $sourceProject->update(['description' => $markdown]);
        $manager = app(MentionManager::class);
        $manager->validateAllMentions($sourceProject, 'description', $markdown, $author);
        $manager->synchronize($sourceProject, 'description', $markdown, true, $author);

        $mention = Mention::query()->firstOrFail();

        $this->assertSame('meeting', $mention->target_type);
        $this->assertSame((string) $targetMeeting->id, (string) $mention->target_id);
        $this->assertTrue($mention->target->is($targetMeeting));
        $this->assertSame($markdown, $sourceProject->fresh()->description);
    }

    public function test_it_prioritizes_contextual_visible_meetings_in_the_meeting_filter(): void
    {
        $author = User::query()->create(['name' => 'Pessoa autora']);
        $hiddenUser = User::query()->create(['name' => 'Pessoa sem acesso']);
        $sourceProject = $this->project('Projeto fonte', $author, 'ADMIN');
        $globalProject = $this->project('Projeto global', $author, 'VIEWER');
        $disabledProject = $this->project('Projeto sem módulo', $author, 'VIEWER');
        $hiddenProject = $this->project('Projeto oculto', $hiddenUser, 'VIEWER');

        $contextualMeeting = $this->meeting('Z reunião contextual', $sourceProject);
        $globalMeeting = $this->meeting('A reunião global', $globalProject);
        $disabledMeeting = $this->meeting('Reunião sem módulo', $disabledProject);
        $hiddenMeeting = $this->meeting('Reunião oculta', $hiddenProject);
        $sourceTask = $this->task('Tarefa no contexto', $sourceProject);

        DB::table('project_modules')
            ->where('project_id', $disabledProject->id)
            ->where('module_id', Module::query()->where('slug', 'meetings')->value('id'))
            ->update(['enabled' => false]);

        $results = app(MentionManager::class)->search(
            $sourceProject,
            '',
            $author,
            'meeting',
        );

        $this->assertSame(
            [$contextualMeeting->id, $globalMeeting->id],
            $results->pluck('id')->all(),
        );
        $this->assertSame(['meeting', 'meeting'], $results->pluck('type')->all());
        $this->assertSame(['Reunião', 'Reunião'], $results->pluck('type_label')->all());
        $this->assertNotContains($disabledMeeting->id, $results->pluck('id')->all());
        $this->assertNotContains($hiddenMeeting->id, $results->pluck('id')->all());

        $taskResults = app(MentionManager::class)->search($sourceTask, '', $author, 'meeting');

        $this->assertSame(
            [$contextualMeeting->id, $globalMeeting->id],
            $taskResults->pluck('id')->all(),
        );
    }

    public function test_project_search_uses_meeting_links_and_agenda_projects_as_context(): void
    {
        $author = User::query()->create(['name' => 'Pessoa autora']);
        $linkedProject = $this->project('Projeto vinculado', $author, 'VIEWER');
        $agendaProject = $this->project('Projeto da pauta', $author, 'VIEWER');
        $globalProject = $this->project('Projeto global', $author, 'VIEWER');
        $meeting = $this->meeting('Reunião fonte', $linkedProject);
        $agendaTask = $this->task('Tarefa da pauta', $agendaProject);
        $meetingItem = $meeting->meetingItems()->create([
            'discussable_type' => 'task',
            'discussable_id' => $agendaTask->id,
            'order' => 1,
        ]);
        $comment = new Comment([
            'commentable_type' => 'meeting',
            'commentable_id' => $meeting->id,
            'is_active' => true,
        ]);
        $comment->setRelation('commentable', $meeting);
        $itemComment = new Comment([
            'commentable_type' => 'meeting_item',
            'commentable_id' => $meetingItem->id,
            'is_active' => true,
        ]);
        $itemComment->setRelation('commentable', $meetingItem);

        foreach ([$meeting, $meetingItem, $comment, $itemComment] as $source) {
            $results = app(MentionManager::class)->search($source, '', $author, 'project');

            $this->assertSame(
                [$linkedProject->id, $agendaProject->id],
                $results->pluck('id')->all(),
            );
            $this->assertSame(
                ['contextual', 'contextual'],
                $results->pluck('scope')->all(),
            );
            $this->assertNotContains($globalProject->id, $results->pluck('id')->all());
        }
    }

    public function test_project_search_uses_the_source_parent_and_direct_children_as_context(): void
    {
        $author = User::query()->create(['name' => 'Pessoa autora']);
        $parent = $this->project('Projeto pai', $author, 'VIEWER');
        $source = $this->project('Projeto fonte', $author, 'VIEWER');
        $child = $this->project('Projeto filho', $author, 'VIEWER');
        $global = $this->project('Projeto global', $author, 'VIEWER');
        $source->update(['parent_id' => $parent->id]);
        $child->update(['parent_id' => $source->id]);

        $results = app(MentionManager::class)->search($source, '', $author, 'project');

        $this->assertSame([$parent->id, $child->id], $results->pluck('id')->all());
        $this->assertNotContains($source->id, $results->pluck('id')->all());
        $this->assertNotContains($global->id, $results->pluck('id')->all());

        $taskResults = app(MentionManager::class)->search(
            $this->task('Tarefa contextual', $source),
            '',
            $author,
            'project',
        );

        $this->assertSame(
            [$source->id, $parent->id, $child->id],
            $taskResults->pluck('id')->all(),
        );
    }

    public function test_it_uses_agenda_projects_and_tasks_for_meeting_item_and_comment_contexts(): void
    {
        $author = User::query()->create(['name' => 'Pessoa autora']);
        $sourceProject = $this->project('Projeto vinculado', $author, 'CONTRIBUTOR');
        $agendaProject = $this->project('Projeto da pauta', $author, 'VIEWER');
        $globalProject = $this->project('Projeto global', $author, 'VIEWER');
        $sourceMeeting = $this->meeting('Reunião fonte', $sourceProject);
        $agendaTask = $this->task('Tarefa da pauta', $agendaProject);

        $sourceMeeting->meetingItems()->create([
            'discussable_type' => 'task',
            'discussable_id' => $agendaTask->id,
            'order' => 1,
        ]);
        $agendaMeeting = $this->meeting('Reunião da pauta', $agendaProject);
        $globalMeeting = $this->meeting('Reunião fora do contexto', $globalProject);
        $meetingItem = $sourceMeeting->meetingItems()->firstOrFail();
        $comment = new Comment([
            'commentable_type' => 'meeting',
            'commentable_id' => $sourceMeeting->id,
            'is_active' => true,
        ]);
        $comment->setRelation('commentable', $sourceMeeting);

        foreach ([$sourceMeeting, $meetingItem, $comment] as $source) {
            $results = app(MentionManager::class)->search($source, '', $author, 'meeting');
            $ids = $results->pluck('id')->all();

            $this->assertLessThan(
                array_search($globalMeeting->id, $ids, true),
                array_search($agendaMeeting->id, $ids, true),
            );
        }
    }

    public function test_the_autocomplete_route_returns_meeting_results_and_its_filter(): void
    {
        $author = User::query()->create(['name' => 'Pessoa autora']);
        $sourceProject = $this->project('Projeto fonte do autocomplete', $author, 'ADMIN');
        $targetMeeting = $this->meeting('Reunião encontrada', $sourceProject);

        $this->actingAs($author)
            ->getJson(route('mentions.selectable', [
                'context_type' => 'project',
                'context_id' => $sourceProject->id,
                'filter' => 'meeting',
            ]))
            ->assertOk()
            ->assertJsonPath('results.0.id', $targetMeeting->id)
            ->assertJsonPath('results.0.type', 'meeting')
            ->assertJsonPath('results.0.type_label', 'Reunião')
            ->assertJsonPath('result_groups.0.type', 'meeting')
            ->assertJsonPath('filters.0.value', 'user')
            ->assertJsonPath('filters.0.label', 'Usuários')
            ->assertJsonPath('filters.3.value', 'meeting')
            ->assertJsonPath('filters.3.label', 'Reuniões');
    }

    public function test_it_presents_a_meeting_through_the_reader_accessible_project_and_current_title(): void
    {
        $owner = User::query()->create(['name' => 'Pessoa proprietária']);
        $reader = User::query()->create(['name' => 'Pessoa leitora']);
        $hiddenProject = $this->project('Projeto não acessível', $owner, 'VIEWER');
        $visibleProject = $this->project('Projeto acessível', $reader, 'VIEWER');
        $targetMeeting = $this->meeting('Título antigo', $hiddenProject, $visibleProject);

        $presentation = app(MentionManager::class)->present(
            'meeting',
            (string) $targetMeeting->id,
            $reader,
        );

        $this->assertSame('available', $presentation['status']);
        $this->assertSame('Título antigo', $presentation['label']);
        $this->assertSame(
            route('projects.meetings.show', [$visibleProject, $targetMeeting]),
            $presentation['url'],
        );
        $this->assertSame('reunião: Título antigo', $presentation['accessible_name']);

        $targetMeeting->update(['title' => 'Título atual']);
        $presentation = app(MentionManager::class)->present(
            'meeting',
            (string) $targetMeeting->id,
            $reader,
        );

        $this->assertSame('Título atual', $presentation['label']);
        $this->assertSame('reunião: Título atual', $presentation['accessible_name']);

        $replacementProject = $this->project('Projeto substituto', $reader, 'VIEWER');
        $targetMeeting->projects()->sync([$hiddenProject->id, $replacementProject->id]);

        $this->assertSame(
            route('projects.meetings.show', [$replacementProject, $targetMeeting]),
            app(MentionManager::class)->present('meeting', (string) $targetMeeting->id, $reader)['url'],
        );

        DB::table('project_modules')
            ->where('project_id', $replacementProject->id)
            ->where('module_id', Module::query()->where('slug', 'meetings')->value('id'))
            ->update(['enabled' => false]);

        $this->assertSame(
            [
                'status' => 'forbidden',
                'type' => 'reunião',
                'message' => 'Menção a reunião: você não tem permissão para visualizar',
            ],
            app(MentionManager::class)->present('meeting', (string) $targetMeeting->id, $reader),
        );
    }

    public function test_markdown_renders_an_authorized_meeting_with_current_title_and_contextual_route(): void
    {
        $reader = User::query()->create(['name' => 'Pessoa leitora']);
        $project = $this->project('Projeto da reunião renderizada', $reader, 'VIEWER');
        $meeting = $this->meeting('Reunião renderizada', $project);

        $this->actingAs($reader);
        $html = app(MarkdownRenderer::class)->render(
            '@[Rótulo histórico](mention:meeting:' . $meeting->id . ')',
        );

        $this->assertStringContainsString('@<a', $html);
        $this->assertStringContainsString(
            route('projects.meetings.show', [$project, $meeting]),
            $html,
        );
        $this->assertStringContainsString('aria-label="reunião: Reunião renderizada"', $html);
        $this->assertStringContainsString('title="reunião: Reunião renderizada"', $html);
        $this->assertStringContainsString('>Reunião renderizada</a>', $html);
    }

    public function test_markdown_does_not_reveal_a_forbidden_or_missing_meeting(): void
    {
        $owner = User::query()->create(['name' => 'Pessoa proprietária']);
        $reader = User::query()->create(['name' => 'Pessoa sem acesso']);
        $project = $this->project('Projeto restrito', $owner, 'VIEWER');
        $meeting = $this->meeting('Título confidencial', $project);

        $this->actingAs($reader);
        $forbiddenHtml = app(MarkdownRenderer::class)->render(
            '@[Rótulo histórico](mention:meeting:' . $meeting->id . ')',
        );
        $missingHtml = app(MarkdownRenderer::class)->render(
            '@[Rótulo inexistente](mention:meeting:999999)',
        );

        $this->assertStringContainsString(
            'Menção a reunião: você não tem permissão para visualizar',
            $forbiddenHtml,
        );
        $this->assertStringNotContainsString('Título confidencial', $forbiddenHtml);
        $this->assertStringNotContainsString('<a', $forbiddenHtml);
        $this->assertStringContainsString('Menção a reunião: destino não encontrado', $missingHtml);
        $this->assertStringNotContainsString('Rótulo inexistente', $missingHtml);
        $this->assertStringNotContainsString('<a', $missingHtml);
    }

    public function test_authorized_incoming_meeting_queries_do_not_reveal_hidden_sources(): void
    {
        $writer = User::query()->create(['name' => 'Pessoa autora']);
        $reader = User::query()->create(['name' => 'Pessoa leitora']);
        $targetProject = $this->project('Projeto destino das entradas', $writer, 'VIEWER');
        $targetProject->users()->attach($reader, ['role' => 'VIEWER']);
        $visibleSource = $this->project('Fonte visível da reunião', $writer, 'CONTRIBUTOR');
        $visibleSource->users()->attach($reader, ['role' => 'VIEWER']);
        $hiddenSource = $this->project('Fonte oculta da reunião', $writer, 'CONTRIBUTOR');
        $targetMeeting = $this->meeting('Reunião com fontes de entrada', $targetProject);
        $markdown = '@[Reunião destino](mention:meeting:' . $targetMeeting->id . ')';

        $visibleSource->update(['description' => $markdown]);
        $hiddenSource->update(['description' => $markdown]);
        $manager = app(MentionManager::class);
        $manager->synchronize($visibleSource, 'description', $markdown, true, $writer);
        $manager->synchronize($hiddenSource, 'description', $markdown, true, $writer);

        $incoming = $manager->incomingMentions($targetMeeting, $reader);

        $this->assertSame([$visibleSource->id], $incoming->pluck('source_id')->all());
    }

    public function test_it_uses_one_validation_message_for_missing_or_inaccessible_meetings(): void
    {
        $author = User::query()->create(['name' => 'Pessoa autora']);
        $owner = User::query()->create(['name' => 'Pessoa proprietária']);
        $sourceProject = $this->project('Projeto da validação', $author, 'ADMIN');
        $targetProject = $this->project('Projeto protegido', $owner, 'VIEWER');
        $targetMeeting = $this->meeting('Reunião protegida', $targetProject);
        $manager = app(MentionManager::class);

        foreach ([
            '@[Reunião protegida](mention:meeting:' . $targetMeeting->id . ')',
            '@[Reunião inexistente](mention:meeting:999999)',
        ] as $markdown) {
            try {
                $manager->validateAllMentions($sourceProject, 'description', $markdown, $author);
                $this->fail('A Menção de Reunião deveria ser rejeitada.');
            } catch (ValidationException $exception) {
                $this->assertSame(
                    ['description' => ['Uma ou mais Menções não existem ou não são permitidas neste contexto.']],
                    $exception->errors(),
                );
            }
        }
    }

    public function test_it_preserves_meeting_mentions_through_soft_deletion_and_cleans_them_after_force_deletion(): void
    {
        $author = User::query()->create(['name' => 'Pessoa autora']);
        $sourceProject = $this->project('Projeto fonte do ciclo', $author, 'ADMIN');
        $targetProject = $this->project('Projeto destino do ciclo', $author, 'VIEWER');
        $targetMeeting = $this->meeting('Reunião restaurável', $targetProject);
        $markdown = '@[Reunião restaurável](mention:meeting:' . $targetMeeting->id . ')';
        $sourceProject->update(['description' => $markdown]);

        $manager = app(MentionManager::class);
        $manager->synchronize($sourceProject, 'description', $markdown, true, $author);

        $targetMeeting->delete();

        $this->assertDatabaseCount('mentions', 1);
        $this->assertSame('missing', $manager->present('meeting', (string) $targetMeeting->id, $author)['status']);

        $targetMeeting->restore();

        $this->assertSame('available', $manager->present('meeting', (string) $targetMeeting->id, $author)['status']);
        $this->assertDatabaseHas('mentions', [
            'target_type' => 'meeting',
            'target_id' => $targetMeeting->id,
        ]);

        $targetMeeting->forceDelete();

        $this->assertDatabaseCount('mentions', 0);
        $this->assertSame($markdown, $sourceProject->fresh()->description);
    }

    public function test_it_omits_and_rejects_a_meeting_mention_to_the_meeting_source_itself(): void
    {
        $author = User::query()->create(['name' => 'Pessoa autora']);
        $project = $this->project('Projeto da reunião autorreferente', $author, 'CONTRIBUTOR');
        $meeting = $this->meeting('Reunião autorreferente', $project);
        $markdown = '@[Reunião autorreferente](mention:meeting:' . $meeting->id . ')';
        $manager = app(MentionManager::class);

        $results = $manager->search($meeting, '', $author, 'meeting');

        $this->assertNotContains($meeting->id, $results->pluck('id')->all());

        $this->expectExceptionObject(ValidationException::withMessages([
            'notes' => 'Uma ou mais Menções não existem ou não são permitidas neste contexto.',
        ]));

        $manager->validateAllMentions($meeting, 'notes', $markdown, $author);
    }

    public function test_the_meeting_notes_route_synchronizes_a_meeting_mention(): void
    {
        $author = User::query()->create(['name' => 'Pessoa autora']);
        $sourceProject = $this->project('Projeto fonte web', $author, 'CONTRIBUTOR');
        $targetProject = $this->project('Projeto destino web', $author, 'VIEWER');
        $sourceMeeting = $this->meeting('Reunião fonte web', $sourceProject);
        $targetMeeting = $this->meeting('Reunião destino web', $targetProject);
        $markdown = '@[Reunião destino](mention:meeting:' . $targetMeeting->id . ')';

        $this->actingAs($author)
            ->patch(route('projects.meetings.updateNotes', [$sourceProject, $sourceMeeting]), [
                'meeting_notes' => $markdown,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('mentions', [
            'source_type' => 'meeting',
            'source_id' => $sourceMeeting->id,
            'source_field' => 'notes',
            'target_type' => 'meeting',
            'target_id' => $targetMeeting->id,
        ]);
        $this->assertSame($markdown, $sourceMeeting->fresh()->notes);
    }

    public function test_the_meeting_item_notes_route_synchronizes_a_meeting_mention(): void
    {
        $author = User::query()->create(['name' => 'Pessoa autora']);
        $sourceProject = $this->project('Projeto fonte do item', $author, 'CONTRIBUTOR');
        $targetProject = $this->project('Projeto destino do item', $author, 'VIEWER');
        $sourceMeeting = $this->meeting('Reunião fonte do item', $sourceProject);
        $targetMeeting = $this->meeting('Reunião destino do item', $targetProject);
        $item = $sourceMeeting->meetingItems()->create([
            'title' => 'Assunto independente',
            'order' => 1,
        ]);
        $markdown = '@[Reunião destino](mention:meeting:' . $targetMeeting->id . ')';

        $this->actingAs($author)
            ->patch(route('projects.meetings.items.updateNotes', [
                $sourceProject,
                $sourceMeeting,
                $item,
            ]), [
                'notes' => $markdown,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('mentions', [
            'source_type' => 'meeting_item',
            'source_id' => $item->id,
            'source_field' => 'notes',
            'target_type' => 'meeting',
            'target_id' => $targetMeeting->id,
        ]);
        $this->assertSame($markdown, $item->fresh()->notes);
    }

    private function project(string $name, User $user, string $role): Project
    {
        $project = Project::query()->create([
            'name' => $name,
            'slug' => str($name)->slug() . '-' . uniqid(),
            'status' => 'ACTIVE',
        ]);
        $project->users()->attach($user, ['role' => $role]);

        DB::table('project_modules')
            ->where('project_id', $project->id)
            ->where('module_id', Module::query()->where('slug', 'meetings')->value('id'))
            ->update(['enabled' => true]);

        return $project;
    }

    private function meeting(string $title, Project ...$projects): Meeting
    {
        $meeting = Meeting::query()->create([
            'title' => $title,
            'status' => 'DRAFT',
        ]);
        $meeting->projects()->sync(collect($projects)->pluck('id')->all());

        return $meeting->fresh('projects');
    }

    private function task(string $title, Project $project): Task
    {
        return Task::query()->create([
            'project_id' => $project->id,
            'title' => $title,
            'status' => 'NEW',
        ]);
    }
}
