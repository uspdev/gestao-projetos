<?php

namespace Tests\Feature;

use App\Mail\TaskAssigned;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\UspPersonSearch;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class TaskCreationAssigneeTest extends TestCase
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

        $this->createSchema();
        (require database_path('migrations/2026_10_15_090000_add_requester_to_tasks_table.php'))->up();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->seedProject();
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');

        parent::tearDown();
    }

    public function test_task_can_be_created_without_an_assignee(): void
    {
        $response = $this->actingAs(User::query()->findOrFail(1))->post(
            route('projects.tasks.store', 'projeto-teste'),
            [
                'title' => 'Tarefa sem responsável',
                'status' => 'NEW',
            ],
        );

        $response->assertRedirect(route('tasks.show', 1));
        $this->assertDatabaseHas('tasks', [
            'id' => 1,
            'title' => 'Tarefa sem responsável',
            'status' => 'NEW',
            'requester' => null,
        ]);
        $this->assertDatabaseCount('task_user', 0);
        $this->assertDatabaseCount('watches', 0);
    }

    public function test_request_endpoints_are_absent_while_manual_task_creation_remains_available(): void
    {
        $project = 'projeto-teste';
        $user = User::query()->findOrFail(1);

        $this->getJson("/api/projects/{$project}/requests")->assertNotFound();
        $this->getJson("/api/projects/{$project}/requests/1")->assertNotFound();
        $this->postJson("/api/projects/{$project}/requests", [
            'title' => 'Solicitação indevida',
        ])->assertMethodNotAllowed();

        $this->actingAs($user)
            ->getJson("/projects/{$project}/requests")
            ->assertNotFound();
        $this->getJson("/projects/{$project}/requests/1")->assertNotFound();
        $this->getJson("/projects/{$project}/requests/1/accept")->assertNotFound();
        $this->postJson("/projects/{$project}/requests/1/accept")->assertMethodNotAllowed();
        $this->patchJson("/projects/{$project}/requests/1/reject")->assertMethodNotAllowed();

        $this->post(route('projects.tasks.store', $project), [
            'title' => 'Tarefa criada manualmente',
            'status' => 'NEW',
        ])->assertRedirect(route('tasks.show', 1));

        $this->assertDatabaseHas('tasks', [
            'project_id' => 1,
            'title' => 'Tarefa criada manualmente',
        ]);
        $this->assertFalse(Schema::hasTable('project_requests'));
    }

    public function test_task_can_be_created_with_one_assignee_who_starts_watching_it(): void
    {
        Mail::fake();

        $response = $this->actingAs(User::query()->findOrFail(1))->post(
            route('projects.tasks.store', 'projeto-teste'),
            [
                'title' => 'Tarefa com responsável',
                'status' => 'NEW',
                'assignee_id' => 2,
            ],
        );

        $response->assertRedirect(route('tasks.show', 1));
        $this->assertDatabaseHas('tasks', [
            'id' => 1,
            'status' => 'ASSIGNED',
        ]);
        $this->assertDatabaseHas('task_user', [
            'task_id' => 1,
            'user_id' => 2,
        ]);
        $this->assertDatabaseHas('watches', [
            'user_id' => 2,
            'watchable_type' => 'task',
            'watchable_id' => 1,
        ]);
        Mail::assertQueued(TaskAssigned::class, function (TaskAssigned $mail): bool {
            return $mail->recipient->id === 2
                && $mail->actor->id === 1
                && $mail->task->id === 1;
        });
    }

    public function test_viewer_cannot_be_selected_as_initial_assignee(): void
    {
        $response = $this->actingAs(User::query()->findOrFail(1))
            ->from(route('projects.show', 'projeto-teste'))
            ->post(route('projects.tasks.store', 'projeto-teste'), [
                'title' => 'Tarefa inválida',
                'status' => 'NEW',
                'assignee_id' => 3,
            ]);

        $response
            ->assertRedirect(route('projects.show', 'projeto-teste'))
            ->assertSessionHasErrors('assignee_id');
        $this->assertDatabaseCount('tasks', 0);
    }

    public function test_creation_modal_shows_only_eligible_assignees_and_allows_a_blank_selection(): void
    {
        $project = Project::query()->with('users')->findOrFail(1);

        View::share('errors', new ViewErrorBag());

        $html = view('module-tasks.partials.components.task-form-modal', [
            'project' => $project,
            'availableTaskTags' => collect(),
        ])->render();

        $this->assertStringContainsString('name="assignee_id"', $html);
        $this->assertStringContainsString('name="requester"', $html);
        $this->assertStringContainsString('Solicitante (opcional)', $html);
        $this->assertStringContainsString('Responsável', $html);
        $this->assertStringContainsString('Sem responsável', $html);
        $this->assertStringContainsString('Criador Admin', $html);
        $this->assertStringContainsString('Colaborador Elegível', $html);
        $this->assertStringNotContainsString('Visualizador Inelegível', $html);
    }

    public function test_requester_select_preserves_previous_text_in_the_submitted_control(): void
    {
        $previous = 'Secretaria & apoio <externo>';
        request()->setLaravelSession(app('session')->driver());
        session()->flash('_old_input', ['requester' => $previous]);
        View::share('errors', new ViewErrorBag());
        $html = view('module-tasks.partials.components.task-form-modal', [
            'project' => Project::findOrFail(1),
            'availableTaskTags' => collect(),
            'availableTaskAssignees' => collect(),
        ])->render();

        $document = new \DOMDocument();
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new \DOMXPath($document);
        $this->assertSame(1, $xpath->query('//select[@name="requester"]')->length);
        $this->assertSame(1, $xpath->query('//*[@name="requester"]')->length);
        $selected = $xpath->query('//select[@name="requester"]/option[@selected]')->item(0);
        $this->assertNotNull($selected);
        $this->assertSame($previous, $selected->getAttribute('value'));
        $this->assertSame($previous, $selected->textContent);
    }

    public function test_requester_can_be_created_updated_and_cleared(): void
    {
        $this->actingAs(User::query()->findOrFail(1))
            ->post(route('projects.tasks.store', 'projeto-teste'), [
                'title' => 'Tarefa solicitada externamente',
                'status' => 'NEW',
                'requester' => '  Secretaria da unidade  ',
            ])->assertRedirect(route('tasks.show', 1));

        $this->assertDatabaseHas('tasks', ['id' => 1, 'requester' => 'Secretaria da unidade']);

        $this->patch(route('tasks.updateInfo', 1), [
            'title' => 'Tarefa solicitada externamente',
            'status' => 'NEW',
            'requester' => 'Ana — atendimento por e-mail',
        ])->assertRedirect(route('tasks.show', 1).'#task-info-1');

        $this->assertDatabaseHas('tasks', ['id' => 1, 'requester' => 'Ana — atendimento por e-mail']);

        // Atualizações que não enviam o campo preservam o valor já informado.
        $this->patch(route('tasks.updateInfo', 1), [
            'title' => 'Título atualizado',
            'status' => 'NEW',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('tasks', ['id' => 1, 'requester' => 'Ana — atendimento por e-mail']);

        $this->patch(route('tasks.updateInfo', 1), [
            'title' => 'Título atualizado',
            'status' => 'NEW',
            'requester' => '   ',
        ])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('tasks', ['id' => 1, 'requester' => null]);
    }

    public function test_requester_validation_applies_to_creation_and_editing(): void
    {
        $this->actingAs(User::query()->findOrFail(1))
            ->post(route('projects.tasks.store', 'projeto-teste'), [
                'title' => 'Tarefa com solicitante',
                'status' => 'NEW',
                'requester' => str_repeat('á', 255),
            ])->assertSessionHasNoErrors();

        foreach ([str_repeat('á', 256), ['solicitante inválido']] as $invalid) {
            $data = ['title' => 'Tarefa inválida', 'status' => 'NEW', 'requester' => $invalid];
            $this->post(route('projects.tasks.store', 'projeto-teste'), $data)
                ->assertSessionHasErrors('requester')
                ->assertSessionHasInput('requester', $invalid);
            $this->patch(route('tasks.updateInfo', 1), $data)
                ->assertSessionHasErrors('requester');
        }

        $this->assertDatabaseCount('tasks', 1);
        $this->assertDatabaseHas('tasks', ['id' => 1, 'requester' => str_repeat('á', 255)]);
    }

    public function test_viewer_cannot_create_or_change_the_requester(): void
    {
        $task = Task::query()->create([
            'project_id' => 1, 'title' => 'Tarefa protegida', 'status' => 'NEW', 'requester' => 'Secretaria',
        ]);

        $this->actingAs(User::query()->findOrFail(3));
        $data = ['title' => 'Tarefa protegida', 'status' => 'NEW', 'requester' => 'Outra pessoa'];
        $this->postJson(route('projects.tasks.store', 'projeto-teste'), $data)->assertForbidden();
        $this->patchJson(route('tasks.updateInfo', $task), $data)->assertForbidden();
        $this->assertSame('Secretaria', $task->fresh()->requester);
    }

    public function test_info_card_and_edit_button_escape_requester_and_show_empty_state(): void
    {
        $this->actingAs(User::query()->findOrFail(1));
        $task = Task::query()->create([
            'project_id' => 1, 'title' => 'Tarefa com origem', 'status' => 'NEW',
            'requester' => '<script>alert("origem")</script> & Secretaria',
        ]);
        $task->setRelation('tags', new \Illuminate\Database\Eloquent\Collection());

        $html = view('module-tasks.partials.show.info-card', compact('task'))->render();
        $this->assertStringContainsString('Solicitante:', $html);
        $this->assertStringContainsString('Editar informações da tarefa', $html);
        $this->assertStringContainsString('data-edit-section="info"', $html);
        $this->assertStringContainsString(e($task->requester), $html);
        $this->assertStringNotContainsString('<script>', $html);

        $button = view('module-tasks.partials.buttons.edit-btn', [
            'task' => $task, 'taskTagIds' => [],
        ])->render();
        $this->assertStringContainsString('data-requester="'.e($task->requester).'"', $button);

        $task->requester = null;
        $this->assertStringContainsString('Não informado',
            view('module-tasks.partials.show.info-card', compact('task'))->render());
    }

    public function test_requester_card_hides_usp_number_without_changing_the_value_for_editing(): void
    {
        $this->actingAs(User::findOrFail(1));
        $task = Task::create([
            'project_id' => 1, 'title' => 'Tarefa com solicitante USP', 'status' => 'NEW',
            'requester' => '13794435 Alexandre Garcia Costa',
        ]);
        $task->setRelation('tags', new \Illuminate\Database\Eloquent\Collection());

        foreach ([
            '13794435 Alexandre Garcia Costa' => 'Alexandre Garcia Costa',
            'Secretaria da unidade' => 'Secretaria da unidade',
            '13794435' => '13794435',
            'Setor 12345' => 'Setor 12345',
        ] as $stored => $displayed) {
            $task->requester = (string) $stored;
            $html = view('module-tasks.partials.show.info-card', compact('task'))->render();
            $document = new \DOMDocument();
            @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
            $xpath = new \DOMXPath($document);
            $requester = $xpath->query('//li[span[normalize-space()="Solicitante:"]]/span[last()]')->item(0);

            $this->assertSame($displayed, $requester->textContent);
            $this->assertStringContainsString('data-requester="'.e((string) $stored).'"', $html);
        }

        $this->assertSame('13794435 Alexandre Garcia Costa', $task->fresh()->requester);
    }

    public function test_title_edit_preserves_all_card_information(): void
    {
        $this->actingAs(User::query()->findOrFail(1));
        $task = Task::query()->create([
            'project_id' => 1, 'title' => 'Título original', 'status' => 'ASSIGNED',
            'requester' => 'Secretaria', 'priority' => 1,
            'start_date' => '2026-09-01', 'due_date' => '2026-09-10',
            'completed_at' => null, 'description' => 'Descrição original',
        ]);
        $task->attachTag('Infraestrutura', \App\Models\Tag::TYPE_TASK);
        $original = $task->fresh()->getAttributes();
        $tagIds = $task->tags->modelKeys();

        $this->patch(route('tasks.updateInfo', $task), [
            'form_context' => 'task-title', 'title' => 'Título atualizado',
            'status' => 'NEW', 'requester' => 'Outro solicitante', 'tags' => [],
        ])->assertRedirect()->assertSessionHasNoErrors();

        $updated = $task->fresh();
        $this->assertSame('Título atualizado', $updated->title);
        foreach (['status', 'requester', 'priority', 'start_date', 'due_date', 'completed_at', 'description'] as $field) {
            $this->assertSame($original[$field], $updated->getRawOriginal($field), $field);
        }
        $this->assertSame($tagIds, $updated->tags->modelKeys());
    }

    public function test_card_information_can_be_updated_without_sending_a_title(): void
    {
        $this->actingAs(User::query()->findOrFail(1));
        $task = Task::query()->create([
            'project_id' => 1, 'title' => 'Título preservado', 'status' => 'NEW',
        ]);
        $task->attachTag('Infraestrutura', \App\Models\Tag::TYPE_TASK);

        $this->patch(route('tasks.updateInfo', $task), [
            'form_context' => 'task-info', 'status' => 'ASSIGNED', 'requester' => 'Secretaria',
            'priority' => 1, 'start_date' => '2026-09-01', 'due_date' => '2026-09-10',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $updated = $task->fresh();
        $this->assertSame('Título preservado', $updated->title);
        $this->assertSame('ASSIGNED', $updated->status->value);
        $this->assertSame('Secretaria', $updated->requester);
        $this->assertNull($updated->completed_at);
        $this->assertCount(0, $updated->tags);

        $this->patch(route('tasks.updateInfo', $task), [
            'form_context' => 'task-info', 'status' => 'NEW', 'title' => 'Alteração indevida',
        ])->assertSessionHasNoErrors();
        $this->assertSame('Título preservado', $task->fresh()->title);
    }

    public function test_separate_edits_validate_their_fields_and_require_authorization(): void
    {
        $task = Task::query()->create([
            'project_id' => 1, 'title' => 'Tarefa protegida', 'status' => 'NEW',
        ]);
        $this->actingAs(User::query()->findOrFail(1));
        $this->patch(route('tasks.updateInfo', $task), [
            'form_context' => 'task-title', 'title' => 'ab',
        ])->assertSessionHasErrors('title')->assertSessionHasInput('form_context', 'task-title');
        $this->patch(route('tasks.updateInfo', $task), [
            'form_context' => 'task-info', 'status' => 'inválido',
        ])->assertSessionHasErrors('status')->assertSessionHasInput('form_context', 'task-info');

        $this->actingAs(User::query()->findOrFail(3));
        foreach (['task-title', 'task-info'] as $context) {
            $this->patchJson(route('tasks.updateInfo', $task), [
                'form_context' => $context, 'title' => 'Novo título', 'status' => 'DONE',
            ])->assertForbidden();
        }
        $task->setRelation('tags', new \Illuminate\Database\Eloquent\Collection());
        $html = view('module-tasks.partials.show.info-card', compact('task'))->render();
        $this->assertStringNotContainsString('Editar informações da tarefa', $html);
    }

    public function test_contributor_can_search_requesters_by_name_or_usp_number(): void
    {
        $people = $this->mock(UspPersonSearch::class);
        $results = [['id' => 1234567, 'text' => '1234567 Maria da Silva']];
        $people->shouldReceive('search')->with('Maria')->once()->andReturn($results);
        $people->shouldReceive('search')->with('1234567')->once()->andReturn($results);

        $this->actingAs(User::findOrFail(2));
        foreach (['Maria', '1234567'] as $term) {
            $this->getJson(route('projects.tasks.requesters.selectable', [
                'project' => 'projeto-teste', 'term' => $term,
            ]))->assertOk()->assertExactJson(['results' => $results]);
        }
    }

    public function test_requester_search_requires_task_creation_permission_and_enabled_module(): void
    {
        $this->mock(UspPersonSearch::class)->shouldNotReceive('search');
        $url = route('projects.tasks.requesters.selectable', [
            'project' => 'projeto-teste', 'term' => 'Maria',
        ]);

        $this->getJson($url)->assertUnauthorized();
        $this->actingAs(User::findOrFail(3))->getJson($url)->assertForbidden();
        DB::table('project_modules')->update(['enabled' => false]);
        $this->actingAs(User::findOrFail(2))->getJson($url)->assertForbidden();
    }

    public function test_requester_search_in_edit_mode_respects_task_authorization(): void
    {
        $task = Task::create(['project_id' => 1, 'title' => 'Tarefa atribuída', 'status' => 'NEW']);
        $task->users()->attach(3);
        $people = $this->mock(UspPersonSearch::class);
        $people->shouldReceive('search')->with('Maria')->once()->andReturn([]);
        $url = route('projects.tasks.requesters.selectable', [
            'project' => 'projeto-teste', 'task_id' => $task->id, 'term' => 'Maria',
        ]);

        $this->actingAs(User::findOrFail(2))->getJson($url)->assertForbidden();
        $this->actingAs(User::findOrFail(3))->getJson($url)->assertOk();
        $task->update(['status' => 'DONE']);
        $this->getJson($url)->assertForbidden();
    }

    public function test_requester_search_validates_input_and_does_not_search_short_terms(): void
    {
        $this->actingAs(User::findOrFail(2));
        $this->mock(UspPersonSearch::class)->shouldNotReceive('search');
        foreach (['', 'Ana', '   '] as $term) {
            $this->getJson(route('projects.tasks.requesters.selectable', [
                'project' => 'projeto-teste', 'term' => $term,
            ]))->assertOk()->assertExactJson(['results' => []]);
        }
        foreach ([['term' => ['Maria']], ['term' => str_repeat('a', 256)], ['task_id' => ['1']]] as $query) {
            $this->getJson(route('projects.tasks.requesters.selectable', [
                'project' => 'projeto-teste', ...$query,
            ]))->assertUnprocessable();
        }

        $other = Project::create(['name' => 'Outro projeto', 'slug' => 'outro-projeto', 'status' => 'ACTIVE']);
        $task = Task::create(['project_id' => $other->id, 'title' => 'Outra tarefa', 'status' => 'NEW']);
        $this->getJson(route('projects.tasks.requesters.selectable', [
            'project' => 'projeto-teste', 'task_id' => $task->id, 'term' => 'Maria',
        ]))->assertNotFound();
    }

    public function test_member_search_keeps_excluding_current_project_members(): void
    {
        \Spatie\Permission\Models\Permission::findOrCreate('admin', User::$hierarquiaNs);
        Schema::table('users', fn(Blueprint $table) => $table->unsignedBigInteger('codpes')->nullable());
        User::findOrFail(2)->update(['codpes' => 1234567]);
        $results = [['id' => 7654321, 'text' => '7654321 Outra pessoa']];
        $people = $this->mock(UspPersonSearch::class);
        $people->shouldReceive('search')->with('Maria', ['1234567'])->once()->andReturn($results);
        $url = route('projects.members.selectable', ['project' => 'projeto-teste', 'term' => 'Maria']);

        $this->actingAs(User::findOrFail(1))->getJson($url)
            ->assertOk()->assertExactJson(['results' => $results]);
        $this->actingAs(User::findOrFail(2))->getJson($url)->assertForbidden();
    }

    public function test_requester_migration_preserves_existing_tasks_and_can_be_reversed(): void
    {
        $migration = require database_path('migrations/2026_10_15_090000_add_requester_to_tasks_table.php');
        $migration->down();
        DB::table('tasks')->insert(['project_id' => 1, 'title' => 'Tarefa anterior', 'status' => 'NEW']);

        $migration->up();
        $this->assertDatabaseHas('tasks', ['title' => 'Tarefa anterior', 'requester' => null]);

        $migration->down();
        $this->assertFalse(Schema::hasColumn('tasks', 'requester'));
        $this->assertDatabaseHas('tasks', ['title' => 'Tarefa anterior']);
    }

    private function seedProject(): void
    {
        $now = now();

        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Criador Admin', 'email' => 'criador@example.test', 'password' => 'secret', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'name' => 'Colaborador Elegível', 'email' => 'colaborador@example.test', 'password' => 'secret', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 3, 'name' => 'Visualizador Inelegível', 'email' => 'visualizador@example.test', 'password' => 'secret', 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('projects')->insert([
            'id' => 1,
            'name' => 'Projeto Teste',
            'slug' => 'projeto-teste',
            'status' => 'ACTIVE',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('project_user')->insert([
            ['project_id' => 1, 'user_id' => 1, 'role' => 'ADMIN', 'pinned' => false, 'created_at' => $now, 'updated_at' => $now],
            ['project_id' => 1, 'user_id' => 2, 'role' => 'CONTRIBUTOR', 'pinned' => false, 'created_at' => $now, 'updated_at' => $now],
            ['project_id' => 1, 'user_id' => 3, 'role' => 'VIEWER', 'pinned' => false, 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('modules')->insert([
            'id' => 1,
            'name' => 'Tarefas',
            'slug' => 'tasks',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('project_modules')->insert([
            'project_id' => 1,
            'module_id' => 1,
            'enabled' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function createSchema(): void
    {
        (require database_path('migrations/2026_04_22_172311_create_tag_tables.php'))->up();

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('project_user', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('user_id');
            $table->string('role');
            $table->boolean('pinned')->default(false);
            $table->timestamps();
            $table->unique(['project_id', 'user_id']);
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
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });

        Schema::create('model_has_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });

        Schema::create('role_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
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
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('module_id');
            $table->boolean('enabled');
            $table->timestamps();
        });

        Schema::create('tasks', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedTinyInteger('priority')->nullable();
            $table->string('status');
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('deleted_by')->nullable();
            $table->boolean('deleted_via_project')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('task_user', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('task_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
            $table->unique(['task_id', 'user_id']);
        });

        Schema::create('watches', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('watchable_type');
            $table->unsignedBigInteger('watchable_id');
            $table->timestamps();
            $table->unique(['user_id', 'watchable_type', 'watchable_id']);
        });

        Schema::create('mentions', function (Blueprint $table): void {
            $table->id();
            $table->string('source_type');
            $table->unsignedBigInteger('source_id');
            $table->string('source_field');
            $table->string('target_type');
            $table->string('target_id');
            $table->unique(['source_type', 'source_id', 'source_field', 'target_type', 'target_id']);
        });

        Schema::create('activity_log', function (Blueprint $table): void {
            $table->id();
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->nullableMorphs('subject');
            $table->nullableMorphs('causer');
            $table->json('properties')->nullable();
            $table->string('event')->nullable();
            $table->string('batch_uuid')->nullable();
            $table->timestamps();
        });
    }
}
