<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Mention;
use App\Models\Meeting;
use App\Models\MeetingItem;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\Mentions\MentionManager;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MeetingRecordsAndIndependentItemsTest extends TestCase
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
        $this->seedMeetingContext();
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');

        parent::tearDown();
    }

    public function test_collaborator_can_edit_meeting_records_after_completion_and_transcription_is_not_logged_as_raw_text(): void
    {
        $ata = "Conclusões\nPróximos passos";
        $transcription = "Participante: conteúdo bruto\nParticipante: outra fala";

        $this->actingAs(User::findOrFail(1));

        $this->patch($this->meetingRoute('ata'), ['ata' => $ata])
            ->assertRedirect();

        $this->patch($this->meetingRoute('transcription'), ['transcription' => $transcription])
            ->assertRedirect();

        $this->assertDatabaseHas('meetings', [
            'id' => 1,
            'ata' => $ata,
            'transcription' => $transcription,
        ]);

        $this->patch($this->meetingRoute('ata'), ['ata' => '   '])
            ->assertRedirect();

        $this->assertDatabaseHas('meetings', [
            'id' => 1,
            'ata' => null,
            'transcription' => $transcription,
        ]);

        $this->patch($this->meetingRoute('ata'), ['ata' => str_repeat('a', 10001)])
            ->assertSessionHasErrors('ata');

        $this->patch($this->meetingRoute('transcription'), ['transcription' => str_repeat('a', 100001)])
            ->assertSessionHasErrors('transcription');

        $activities = ActivityLog::query()
            ->where('log_name', 'meeting')
            ->where('subject_id', 1)
            ->get();

        $this->assertTrue($activities->contains(function (ActivityLog $activity) use ($ata) {
            return data_get($activity->properties, 'attributes.ata') === $ata;
        }));
        $this->assertTrue($activities->contains(function (ActivityLog $activity) use ($transcription) {
            $properties = json_encode($activity->properties, JSON_UNESCAPED_UNICODE);

            return str_contains($properties, 'transcription_length')
                && !str_contains($properties, $transcription);
        }));

        $this->actingAs(User::findOrFail(2));

        $this->patch($this->meetingRoute('ata'), ['ata' => 'Alteração não autorizada'])
            ->assertForbidden();
    }

    public function test_meeting_page_separates_prior_notes_from_the_meeting_record(): void
    {
        DB::table('meetings')->where('id', 1)->update([
            'ata' => 'Ata exibida',
            'transcription' => "Transcrição exibida\ncom quebra",
        ]);

        $this->actingAs(User::findOrFail(2));

        $this->get('/projects/projeto-teste/meetings/1')
            ->assertOk()
            ->assertSee('Anotações prévias')
            ->assertSee('Ata exibida')
            ->assertSee('Transcrição exibida')
            ->assertSeeInOrder(['Anotações prévias', 'Pauta', 'Registro da reunião'])
            ->assertSee('data-target="#meeting-ata-display"', false)
            ->assertSee('data-target="#meeting-transcription-display"', false)
            ->assertSee('aria-expanded="false"', false)
            ->assertSee('meeting-record-toggle-icon', false)
            ->assertSee('class="collapse" id="meeting-ata-display"', false)
            ->assertSee('class="collapse" id="meeting-transcription-display"', false)
            ->assertDontSee('class="collapse show" id="meeting-ata-display"', false)
            ->assertDontSee('class="collapse show" id="meeting-transcription-display"', false);
    }

    public function test_project_members_start_watching_a_new_meeting(): void
    {
        $this->actingAs(User::findOrFail(1));

        $this->post('/projects/projeto-teste/meetings', [
            'title' => 'Nova reunião acompanhada',
            'scheduled_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'location' => 'Sala de reunião',
            'notes' => null,
            'status' => 'DRAFT',
            'projects' => [1],
        ])->assertRedirect();

        $meetingId = DB::table('meetings')
            ->where('title', 'Nova reunião acompanhada')
            ->value('id');

        $this->assertDatabaseHas('watches', [
            'user_id' => 1,
            'watchable_type' => 'meeting',
            'watchable_id' => $meetingId,
        ]);
        $this->assertDatabaseHas('watches', [
            'user_id' => 2,
            'watchable_type' => 'meeting',
            'watchable_id' => $meetingId,
        ]);
    }

    public function test_meeting_markdown_consumers_share_safe_rendering_while_records_remain_plain_text(): void
    {
        $markdown = '**Seguro** [Projeto](https://example.test) <script>alert(1)</script>';

        DB::table('meetings')->where('id', 1)->update([
            'notes' => $markdown,
            'ata' => '**Ata literal** <script>alert(2)</script>',
            'transcription' => '**Transcrição literal** <script>alert(3)</script>',
        ]);
        DB::table('meeting_items')->insert([
            'id' => 1,
            'meeting_id' => 1,
            'title' => 'Item com Markdown',
            'order' => 1,
            'notes' => $markdown,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('comments')->insert([
            'id' => 1,
            'user_id' => 1,
            'commentable_type' => 'meeting',
            'commentable_id' => 1,
            'text' => $markdown,
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs(User::findOrFail(2));

        $response = $this->get('/projects/projeto-teste/meetings/1')->assertOk();

        $this->assertSame(3, substr_count($response->getContent(), '<strong>Seguro</strong>'));
        $this->assertSame(3, substr_count($response->getContent(), 'href="https://example.test" target="_blank" rel="noopener noreferrer"'));
        $this->assertSame(3, substr_count($response->getContent(), 'class="markdown-content"'));
        $this->assertSame(3, substr_count($response->getContent(), 'aria-label="Conteúdo em Markdown"'));
        $this->assertSame(3, substr_count($response->getContent(), 'role="region" tabindex="0"'));
        $response
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertSee('**Ata literal** &lt;script&gt;alert(2)&lt;/script&gt;', false)
            ->assertSee('**Transcrição literal** &lt;script&gt;alert(3)&lt;/script&gt;', false)
            ->assertDontSee('<strong>Ata literal</strong>', false)
            ->assertDontSee('<strong>Transcrição literal</strong>', false);
    }

    public function test_project_and_project_type_descriptions_use_the_safe_markdown_renderer(): void
    {
        $markdown = '**Descrição segura** [Destino](javascript:alert(1))';

        DB::table('project_types')->where('id', 1)->update(['description' => $markdown]);
        DB::table('projects')->where('id', 1)->update(['description' => $markdown]);

        $this->actingAs(User::findOrFail(2));

        $response = $this->get('/projects/projeto-teste')->assertOk();

        $this->assertSame(2, substr_count($response->getContent(), '<strong>Descrição segura</strong>'));
        $response
            ->assertDontSee('href="javascript:alert(1)"', false)
            ->assertDontSee('class="hljs', false);
    }

    public function test_task_description_uses_the_safe_markdown_renderer(): void
    {
        DB::table('tasks')->insert([
            'id' => 1,
            'project_id' => 1,
            'title' => 'Tarefa com Markdown',
            'description' => '**Tarefa segura** <img src=x onerror=alert(1)>',
            'priority' => 3,
            'status' => 'ASSIGNED',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs(User::findOrFail(2));

        $this->get('/tasks/1')
            ->assertOk()
            ->assertSee('<strong>Tarefa segura</strong>', false)
            ->assertSee('&lt;img src=x onerror=alert(1)&gt;', false)
            ->assertDontSee('<img src=x onerror=alert(1)>', false);
    }

    public function test_task_duplication_rebuilds_mentions_for_the_copy_after_persisting_its_links(): void
    {
        $markdown = '@[Visualizador](mention:user:2)';
        $source = Task::query()->create([
            'project_id' => 1,
            'title' => 'Tarefa com Menção',
            'description' => $markdown,
            'priority' => 3,
            'status' => 'ASSIGNED',
        ]);

        app(MentionManager::class)->synchronize($source, 'description', $markdown);

        $copy = $source->duplicate();

        $this->assertTrue($copy->outgoingMentions()
            ->where('source_field', 'description')
            ->where('target_type', 'user')
            ->where('target_id', 2)
            ->exists());
        $this->assertSame(2, Mention::query()->count());
    }

    public function test_meeting_duplication_rebuilds_mentions_for_the_copy_after_persisting_its_projects(): void
    {
        $markdown = '@[Visualizador](mention:user:2)';
        DB::table('meetings')->where('id', 1)->update(['notes' => $markdown]);
        $source = Meeting::query()->findOrFail(1);

        app(MentionManager::class)->synchronize($source, 'notes', $markdown);

        $copy = $source->duplicate(['scheduled_at' => now()->addDay()]);

        $this->assertTrue($copy->outgoingMentions()
            ->where('source_field', 'notes')
            ->where('target_type', 'user')
            ->where('target_id', 2)
            ->exists());
        $this->assertSame(2, Mention::query()->count());
    }

    public function test_meeting_duplication_preserves_item_markdown_and_rebuilds_item_mentions(): void
    {
        $markdown = '@[Visualizador](mention:user:2)';
        $sourceItem = MeetingItem::query()->create([
            'meeting_id' => 1,
            'title' => 'Item independente',
            'order' => 1,
            'notes' => $markdown,
        ]);

        app(MentionManager::class)->synchronize($sourceItem, 'notes', $markdown);

        $copy = Meeting::query()->findOrFail(1)->duplicate([
            'scheduled_at' => now()->addDay(),
        ]);
        $copyItem = $copy->meetingItems()->firstOrFail();

        $this->assertSame($markdown, $copyItem->notes);
        $this->assertTrue($sourceItem->outgoingMentions()
            ->where('source_field', 'notes')
            ->where('target_type', 'user')
            ->where('target_id', 2)
            ->exists());
        $this->assertTrue($copyItem->outgoingMentions()
            ->where('source_field', 'notes')
            ->where('target_type', 'user')
            ->where('target_id', 2)
            ->exists());
        $this->assertSame(2, Mention::query()->count());
    }

    public function test_project_duplication_preserves_historical_mentions_without_copying_members_or_remapping_targets(): void
    {
        $this->actingAs(User::findOrFail(1));
        DB::table('meetings')->where('id', 1)->update([
            'scheduled_at' => now()->addDay(),
        ]);

        $targetTask = Task::query()->create([
            'project_id' => 1,
            'title' => 'Tarefa original',
            'description' => 'Descrição original',
            'priority' => 3,
            'status' => 'ASSIGNED',
        ]);
        $source = Project::query()->findOrFail(1);
        $markdown = implode(' ', [
            '@[Tarefa original](mention:task:' . $targetTask->id . ')',
            '@[Reunião original](mention:meeting:1)',
        ]);

        DB::table('projects')->where('id', $source->id)->update(['description' => $markdown]);
        $source->refresh();
        app(MentionManager::class)->synchronize($source, 'description', $markdown);

        $copy = $source->duplicate([
            'name' => 'Projeto copiado com entidades',
            'copy_members' => false,
            'copy_tasks' => true,
            'copy_meetings' => true,
        ]);
        $copyTask = Task::query()
            ->where('project_id', $copy->id)
            ->where('title', 'Tarefa original')
            ->firstOrFail();
        $copyMeetingId = DB::table('meeting_projects')
            ->where('project_id', $copy->id)
            ->value('meeting_id');

        $this->assertNotNull($copyMeetingId);
        $this->assertNotSame(1, (int) $copyMeetingId);
        $this->assertSame($markdown, $copy->description);
        $this->assertSame([1], $copy->users()->pluck('users.id')->sort()->values()->all());

        foreach ([
            ['target_type' => 'task', 'target_id' => $targetTask->id],
            ['target_type' => 'meeting', 'target_id' => 1],
        ] as $target) {
            $this->assertDatabaseHas('mentions', [
                'source_type' => 'project',
                'source_id' => $source->id,
                ...$target,
            ]);
            $this->assertDatabaseHas('mentions', [
                'source_type' => 'project',
                'source_id' => $copy->id,
                ...$target,
            ]);
        }

        $this->assertDatabaseMissing('mentions', [
            'source_type' => 'project',
            'source_id' => $copy->id,
            'target_type' => 'task',
            'target_id' => $copyTask->id,
        ]);
        $this->assertDatabaseMissing('mentions', [
            'source_type' => 'project',
            'source_id' => $copy->id,
            'target_type' => 'meeting',
            'target_id' => $copyMeetingId,
        ]);
        $this->assertSame(4, Mention::query()->count());
    }

    public function test_project_duplication_with_members_does_not_add_users_from_copied_mentions(): void
    {
        $this->actingAs(User::findOrFail(1));
        $source = Project::query()->findOrFail(1);
        $markdown = '@[Visualizador](mention:user:2)';

        DB::table('projects')->where('id', $source->id)->update(['description' => $markdown]);
        $source->refresh();
        app(MentionManager::class)->synchronize($source, 'description', $markdown);

        $copy = $source->duplicate([
            'name' => 'Projeto copiado com membros',
            'copy_members' => true,
            'copy_tasks' => false,
            'copy_meetings' => false,
        ]);

        $this->assertSame([1, 2], $copy->users()->pluck('users.id')->sort()->values()->all());
        $this->assertDatabaseHas('mentions', [
            'source_type' => 'project',
            'source_id' => $copy->id,
            'source_field' => 'description',
            'target_type' => 'user',
            'target_id' => 2,
        ]);
        $this->assertSame(2, Mention::query()->count());
    }

    public function test_duplication_rolls_back_when_mention_rebuild_fails(): void
    {
        $source = Task::query()->create([
            'project_id' => 1,
            'title' => 'Tarefa com falha de índice',
            'description' => 'Descrição',
            'priority' => 3,
            'status' => 'ASSIGNED',
        ]);

        app()->instance(MentionManager::class, new class
        {
            public function rebuildSource(\Illuminate\Database\Eloquent\Model $source): int
            {
                throw new \RuntimeException('Falha ao sincronizar Menções.');
            }
        });

        try {
            $source->duplicate();
            $this->fail('A duplicação deveria falhar.');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Falha ao sincronizar Menções.', $exception->getMessage());
        }

        $this->assertSame(1, Task::query()->count());
        $this->assertSame(0, Mention::query()->count());
    }

    public function test_markdown_fields_expose_their_editor_profiles_without_enabling_plain_text_records(): void
    {
        DB::table('project_user')->where('user_id', 1)->update(['role' => 'ADMIN']);
        DB::table('meetings')->where('id', 1)->update(['status' => 'DRAFT']);
        DB::table('meeting_items')->insert([
            'id' => 1,
            'meeting_id' => 1,
            'title' => 'Item independente',
            'order' => 1,
            'notes' => 'Preparação do item',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('tasks')->insert([
            'id' => 1,
            'project_id' => 1,
            'title' => 'Tarefa teste',
            'description' => 'Descrição da tarefa',
            'priority' => 3,
            'status' => 'ASSIGNED',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs(User::findOrFail(1));

        $projectPage = $this->get('/projects/projeto-teste')->assertOk()->getContent();
        $taskPage = $this->get('/tasks/1')->assertOk()->getContent();
        $meetingPage = $this->get('/projects/projeto-teste/meetings/1')->assertOk()->getContent();

        $this->assertStringContainsString('id="project-description-edit-1-textarea"', $projectPage);
        $this->assertStringContainsString('id="task-description-edit-1-textarea"', $taskPage);
        $this->assertStringContainsString('id="meeting-notes-edit-1-textarea"', $meetingPage);
        $this->assertStringContainsString('id="meeting-item-notes-edit-1-textarea"', $meetingPage);

        foreach ([$projectPage, $taskPage, $meetingPage] as $page) {
            $this->assertStringContainsString('data-markdown-profile="full"', $page);
            $this->assertStringContainsString('data-markdown-profile="compact"', $page);
            $this->assertMatchesRegularExpression(
                '/data-markdown-preview-url="[^"]*\/markdown\/preview"/',
                $page
            );
        }

        $this->assertDoesNotMatchRegularExpression(
            '/id="meeting-(?:ata|transcription)-textarea"[^>]*data-markdown-editor/',
            $meetingPage
        );
    }

    public function test_prior_notes_are_locked_when_completed_and_editable_again_after_reopening(): void
    {
        $this->actingAs(User::findOrFail(1));

        $this->patch('/projects/projeto-teste/meetings/1/notes', ['meeting_notes' => 'Tentativa bloqueada'])
            ->assertForbidden();

        $this->patch('/projects/projeto-teste/meetings/1/status', ['status' => 'SCHEDULED'])
            ->assertRedirect();

        $this->patch('/projects/projeto-teste/meetings/1/notes', ['meeting_notes' => 'Preparação revisada'])
            ->assertRedirect();

        $this->assertDatabaseHas('meetings', [
            'id' => 1,
            'status' => 'SCHEDULED',
            'notes' => 'Preparação revisada',
        ]);
    }

    public function test_collaborator_can_add_independent_items_in_any_agenda_position(): void
    {
        DB::table('meetings')->where('id', 1)->update(['status' => 'DRAFT']);
        DB::table('meeting_items')->insert([
            'meeting_id' => 1,
            'discussable_type' => 'project',
            'discussable_id' => 1,
            'order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs(User::findOrFail(1));

        $this->post('/projects/projeto-teste/meetings/1/items', [
            'item_type' => 'independent',
            'title' => '  Ideia estratégica  ',
            'order' => 1,
        ])->assertRedirect();

        $independentItem = DB::table('meeting_items')
            ->where('meeting_id', 1)
            ->where('title', 'Ideia estratégica')
            ->first();

        $this->assertNotNull($independentItem);
        $this->assertSame(1, $independentItem->order);
        $this->assertDatabaseHas('meeting_items', [
            'discussable_type' => 'project',
            'discussable_id' => 1,
            'order' => 2,
        ]);

        $this->post('/projects/projeto-teste/meetings/1/items', [
            'item_type' => 'independent',
            'title' => 'Outro assunto',
            'order' => 2,
        ])->assertRedirect();

        $this->assertDatabaseHas('meeting_items', [
            'title' => 'Outro assunto',
            'order' => 2,
        ]);
        $this->assertDatabaseHas('meeting_items', [
            'discussable_type' => 'project',
            'discussable_id' => 1,
            'order' => 3,
        ]);

        $this->get('/projects/projeto-teste/meetings/1')
            ->assertOk()
            ->assertSee('Ideia estratégica')
            ->assertSee('Outro assunto')
            ->assertSee('Título do item')
            ->assertSee('Adicionar item de pauta')
            ->assertSee('name="item_type"', false)
            ->assertDontSee('name="discussable_type"', false);
    }

    public function test_independent_item_title_is_validated_and_locked_only_while_meeting_is_completed(): void
    {
        DB::table('meetings')->where('id', 1)->update(['status' => 'DRAFT']);
        DB::table('meeting_items')->insert([
            'id' => 1,
            'meeting_id' => 1,
            'title' => 'Título original',
            'order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs(User::findOrFail(1));

        $this->patch('/projects/projeto-teste/meetings/1/items/1', ['title' => '  Título atualizado  '])
            ->assertRedirect();

        $this->assertDatabaseHas('meeting_items', ['id' => 1, 'title' => 'Título atualizado']);

        $this->patch('/projects/projeto-teste/meetings/1/items/1', ['title' => 'ab'])
            ->assertSessionHasErrors('title');

        $this->patch('/projects/projeto-teste/meetings/1/status', ['status' => 'COMPLETED'])
            ->assertRedirect();

        $this->patch('/projects/projeto-teste/meetings/1/items/1', ['title' => 'Alteração bloqueada'])
            ->assertForbidden();

        $this->patch('/projects/projeto-teste/meetings/1/status', ['status' => 'SCHEDULED'])
            ->assertRedirect();

        $this->patch('/projects/projeto-teste/meetings/1/items/1', ['title' => 'Título reaberto'])
            ->assertRedirect();

        $this->assertDatabaseHas('meeting_items', ['id' => 1, 'title' => 'Título reaberto']);
    }

    public function test_meeting_items_do_not_accept_comments(): void
    {
        DB::table('meetings')->where('id', 1)->update(['status' => 'DRAFT']);
        DB::table('meeting_items')->insert([
            [
                'id' => 1,
                'meeting_id' => 1,
                'discussable_type' => null,
                'discussable_id' => null,
                'title' => 'Assunto sem comentários próprios',
                'order' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'meeting_id' => 1,
                'discussable_type' => 'project',
                'discussable_id' => 1,
                'title' => null,
                'order' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->actingAs(User::findOrFail(1));

        $this->post('/comments', [
            'commentable_type' => 'meeting_item',
            'commentable_id' => 1,
            'text' => 'Comentário não permitido',
        ])->assertForbidden();

        $this->assertDatabaseMissing('comments', [
            'commentable_type' => 'meeting_item',
            'commentable_id' => 1,
        ]);

        $this->post('/comments', [
            'commentable_type' => 'meeting_item',
            'commentable_id' => 2,
            'text' => 'Comentário não permitido em item vinculado',
        ])->assertForbidden();

        $this->assertDatabaseMissing('comments', [
            'commentable_type' => 'meeting_item',
            'commentable_id' => 2,
        ]);
    }

    public function test_independent_item_notes_follow_markdown_and_completion_rules_and_removal_reindexes_the_agenda(): void
    {
        DB::table('meetings')->where('id', 1)->update(['status' => 'DRAFT']);
        DB::table('meeting_items')->insert([
            ['id' => 1, 'meeting_id' => 1, 'title' => 'Primeiro assunto', 'order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'meeting_id' => 1, 'title' => 'Segundo assunto', 'order' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->actingAs(User::findOrFail(1));

        $this->patch('/projects/projeto-teste/meetings/1/items/1/notes', [
            'notes' => "**Preparação**\nDetalhes do assunto",
        ])->assertRedirect();

        $this->assertDatabaseHas('meeting_items', [
            'id' => 1,
            'notes' => "**Preparação**\nDetalhes do assunto",
        ]);

        $this->patch('/projects/projeto-teste/meetings/1/status', ['status' => 'COMPLETED'])
            ->assertRedirect();

        $this->patch('/projects/projeto-teste/meetings/1/items/1/notes', ['notes' => 'Alteração bloqueada'])
            ->assertForbidden();

        $this->delete('/projects/projeto-teste/meetings/1/items/1')
            ->assertRedirect()
            ->assertSessionHasErrors('meeting_item');

        $this->patch('/projects/projeto-teste/meetings/1/status', ['status' => 'SCHEDULED'])
            ->assertRedirect();

        $this->delete('/projects/projeto-teste/meetings/1/items/1')
            ->assertRedirect();

        $this->assertDatabaseHas('meeting_items', [
            'id' => 2,
            'order' => 1,
        ]);
    }

    public function test_existing_project_items_continue_to_be_added_with_their_linked_representation(): void
    {
        DB::table('meetings')->where('id', 1)->update(['status' => 'DRAFT']);

        $this->actingAs(User::findOrFail(1));

        $this->post('/projects/projeto-teste/meetings/1/items', [
            'item_type' => 'project',
            'discussable_id' => 1,
            'order' => 1,
        ])->assertRedirect();

        $this->assertDatabaseHas('meeting_items', [
            'meeting_id' => 1,
            'discussable_type' => 'project',
            'discussable_id' => 1,
            'title' => null,
            'order' => 1,
        ]);
    }

    private function meetingRoute(string $record): string
    {
        return "/projects/projeto-teste/meetings/1/{$record}";
    }

    private function seedMeetingContext(): void
    {
        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Colaborador', 'email' => 'colaborador@example.test', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Visualizador', 'email' => 'visualizador@example.test', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('permissions')->insert(collect(['admin', 'boss', 'manager', 'poweruser', 'user'])
            ->map(fn (string $name) => [
                'name' => $name,
                'guard_name' => 'senhaunica',
                'created_at' => now(),
                'updated_at' => now(),
            ])
            ->all());
        DB::table('modules')->insert([
            ['id' => 1, 'name' => 'Reuniões', 'slug' => 'meetings', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'name' => 'Tarefas', 'slug' => 'tasks', 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('project_types')->insert([
            'id' => 1,
            'name' => 'Tipo teste',
            'slug' => 'tipo-teste',
            'description' => null,
            'enabled' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('projects')->insert([
            'id' => 1,
            'name' => 'Projeto teste',
            'slug' => 'projeto-teste',
            'status' => 'ACTIVE',
            'permission_inheritance' => 'NONE',
            'project_type_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('project_modules')->insert([
            ['project_id' => 1, 'module_id' => 1, 'enabled' => true, 'created_at' => now(), 'updated_at' => now()],
            ['project_id' => 1, 'module_id' => 2, 'enabled' => true, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('project_user')->insert([
            ['user_id' => 1, 'project_id' => 1, 'role' => 'CONTRIBUTOR', 'pinned' => false, 'created_at' => now(), 'updated_at' => now()],
            ['user_id' => 2, 'project_id' => 1, 'role' => 'VIEWER', 'pinned' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('meetings')->insert([
            'id' => 1,
            'title' => 'Reunião teste',
            'notes' => 'Anotações prévias',
            'status' => 'COMPLETED',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('meeting_projects')->insert(['meeting_id' => 1, 'project_id' => 1]);
    }

    private function createSchema(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
        });

        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });

        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });

        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
        });

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('password')->nullable();
            $table->integer('codpes')->nullable();
            $table->timestamps();
        });

        Schema::create('modules', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('project_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('project_type_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_type_id');
            $table->foreignId('module_id');
            $table->boolean('enabled')->default(true);
            $table->boolean('required')->default(false);
            $table->boolean('editable')->default(true);
            $table->json('config')->nullable();
            $table->timestamps();
        });

        Schema::create('phases', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('color')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('project_type_phases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_type_id');
            $table->foreignId('phase_id');
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_initial')->default(false);
            $table->boolean('is_final')->default(false);
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status');
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable();
            $table->string('visibility')->nullable();
            $table->string('permission_inheritance')->nullable();
            $table->foreignId('project_type_id')->nullable();
            $table->foreignId('phase_id')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->foreignId('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('project_modules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id');
            $table->foreignId('module_id');
            $table->boolean('enabled');
            $table->timestamps();
        });

        Schema::create('project_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('project_id');
            $table->string('role');
            $table->boolean('pinned')->default(false);
            $table->timestamps();
        });

        Schema::create('meetings', function (Blueprint $table) {
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

        Schema::create('meeting_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id');
            $table->foreignId('project_id');
        });

        Schema::create('meeting_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id');
            $table->string('discussable_type')->nullable();
            $table->unsignedBigInteger('discussable_id')->nullable();
            $table->string('title')->nullable();
            $table->unsignedInteger('order');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('commentable_type');
            $table->unsignedBigInteger('commentable_id');
            $table->foreignId('parent_id')->nullable();
            $table->text('text');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('activity_log', function (Blueprint $table) {
            $table->id();
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('causer_type')->nullable();
            $table->unsignedBigInteger('causer_id')->nullable();
            $table->json('properties')->nullable();
            $table->string('batch_uuid')->nullable();
            $table->string('event')->nullable();
            $table->timestamps();
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('priority')->nullable();
            $table->string('status');
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->foreignId('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('task_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id');
            $table->foreignId('user_id');
            $table->timestamps();
        });

        Schema::create('watches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id');
            $table->string('watchable_type');
            $table->unsignedBigInteger('watchable_id');
            $table->timestamps();
            $table->unique(['user_id', 'watchable_type', 'watchable_id']);
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->json('slug');
            $table->string('type')->nullable();
            $table->integer('order_column')->nullable();
            $table->string('color')->default('badge-dark');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('taggables', function (Blueprint $table) {
            $table->foreignId('tag_id');
            $table->string('taggable_type');
            $table->unsignedBigInteger('taggable_id');
        });

        $migration = require database_path('migrations/2026_07_21_090000_create_media_table.php');
        $migration->up();
        (require database_path('migrations/2026_07_22_090000_create_meeting_file_shares_table.php'))->up();
        (require database_path('migrations/2026_07_23_090000_create_mentions_table.php'))->up();
    }
}
