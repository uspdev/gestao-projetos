<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TaskListOrderingTest extends TestCase
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
        $this->seedTasks();
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');

        parent::tearDown();
    }

    public function test_project_task_list_orders_highest_priority_first_and_unprioritized_tasks_last(): void
    {
        $tasks = Project::query()
            ->findOrFail(1)
            ->tasks()
            ->orderByPriority()
            ->latest()
            ->pluck('title');

        $this->assertSame([
            'Urgente do Projeto A',
            'Alta do Projeto A',
            'Média do Projeto A',
            'Baixa do Projeto A',
            'Sem prioridade do Projeto A',
        ], $tasks->all());
    }

    public function test_dashboard_list_orders_by_priority_before_project_name(): void
    {
        $tasks = User::query()
            ->findOrFail(1)
            ->tasksByStatus('list')
            ->pluck('title');

        $this->assertSame([
            'Urgente do Projeto Z',
            'Urgente do Projeto A',
            'Alta do Projeto A',
            'Média do Projeto A',
            'Baixa do Projeto A',
            'Sem prioridade do Projeto A',
        ], $tasks->all());
    }

    private function seedTasks(): void
    {
        $now = now();

        DB::table('users')->insert([
            'id' => 1,
            'name' => 'Usuário',
            'email' => 'usuario@example.test',
            'password' => 'secret',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('projects')->insert([
            [
                'id' => 1,
                'name' => 'Projeto A',
                'slug' => 'projeto-a',
                'status' => 'ACTIVE',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'id' => 2,
                'name' => 'Projeto Z',
                'slug' => 'projeto-z',
                'status' => 'ACTIVE',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
        DB::table('modules')->insert([
            'id' => 1,
            'name' => 'Tarefas',
            'slug' => 'tasks',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('project_modules')->insert([
            ['project_id' => 1, 'module_id' => 1, 'enabled' => true, 'created_at' => $now, 'updated_at' => $now],
            ['project_id' => 2, 'module_id' => 1, 'enabled' => true, 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('tasks')->insert([
            ['id' => 1, 'project_id' => 1, 'title' => 'Baixa do Projeto A', 'priority' => 4, 'status' => 'NEW', 'created_at' => $now->copy()->subMinutes(5), 'updated_at' => $now],
            ['id' => 2, 'project_id' => 1, 'title' => 'Urgente do Projeto A', 'priority' => 1, 'status' => 'NEW', 'created_at' => $now->copy()->subMinutes(4), 'updated_at' => $now],
            ['id' => 3, 'project_id' => 1, 'title' => 'Sem prioridade do Projeto A', 'priority' => null, 'status' => 'NEW', 'created_at' => $now->copy()->subMinutes(3), 'updated_at' => $now],
            ['id' => 4, 'project_id' => 1, 'title' => 'Alta do Projeto A', 'priority' => 2, 'status' => 'NEW', 'created_at' => $now->copy()->subMinutes(2), 'updated_at' => $now],
            ['id' => 5, 'project_id' => 2, 'title' => 'Urgente do Projeto Z', 'priority' => 1, 'status' => 'NEW', 'created_at' => $now->copy()->subMinute(), 'updated_at' => $now],
            ['id' => 6, 'project_id' => 1, 'title' => 'Média do Projeto A', 'priority' => 3, 'status' => 'NEW', 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('task_user')->insert(
            collect(range(1, 6))->map(fn(int $taskId): array => [
                'task_id' => $taskId,
                'user_id' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all()
        );
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->string('status');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('modules', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug');
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
            $table->unsignedTinyInteger('priority')->nullable();
            $table->string('status');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('task_user', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('task_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
        });
    }
}
