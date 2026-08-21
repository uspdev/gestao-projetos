<?php

namespace Tests\Feature;

use App\Mail\TaskAssigned;
use App\Models\Project;
use App\Models\User;
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

        $response->assertRedirect(route('tasks.show', 1).'#task-1');
        $this->assertDatabaseHas('tasks', [
            'id' => 1,
            'title' => 'Tarefa sem responsável',
            'status' => 'NEW',
        ]);
        $this->assertDatabaseCount('task_user', 0);
        $this->assertDatabaseCount('watches', 0);
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

        $response->assertRedirect(route('tasks.show', 1).'#task-1');
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
        $this->assertStringContainsString('Responsável', $html);
        $this->assertStringContainsString('Sem responsável', $html);
        $this->assertStringContainsString('Criador Admin', $html);
        $this->assertStringContainsString('Colaborador Elegível', $html);
        $this->assertStringNotContainsString('Visualizador Inelegível', $html);
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
