<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\Project;
use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Uspdev\ApiKeys\Contracts\ApiKeyManager;

class TaskApiTest extends TestCase
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

        Module::query()->create([
            'name' => 'Tarefas',
            'slug' => 'tasks',
        ]);
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');

        parent::tearDown();
    }

    public function test_task_routes_require_tasks_read_and_accept_viewer_and_contributor_keys(): void
    {
        $project = $this->project('Projeto protegido');
        $task = $this->task($project);

        $this->getJson($this->indexUrl($project))
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Unauthenticated.']);

        $this->withToken($this->tokenFor($project, 'unknown'))
            ->getJson($this->indexUrl($project))
            ->assertForbidden()
            ->assertExactJson(['message' => 'Forbidden.']);

        $this->withToken($this->tokenFor($project, 'viewer'))
            ->getJson($this->showUrl($project, $task))
            ->assertOk()
            ->assertJsonPath('data.id', $task->id);

        $this->withToken($this->tokenFor($project, 'contributor'))
            ->getJson($this->indexUrl($project))
            ->assertOk()
            ->assertJsonPath('data.0.id', $task->id);
    }

    public function test_list_and_detail_expose_the_integral_documented_representation_only(): void
    {
        $project = $this->project('Projeto com contexto integral');
        $description = "## Contexto integral\n\n".str_repeat('Descrição com **Markdown**, sem renderização. ', 40).'Fim da descrição.';
        $task = $this->task($project, [
            'title' => 'Documentar integração',
            'description' => $description,
            'status' => 'DONE',
            'priority' => 2,
            'start_date' => '2026-09-01',
            'due_date' => '2026-09-12',
            'completed_at' => '2026-09-10 15:30:00',
            'created_at' => '2026-08-30 09:00:00',
            'updated_at' => '2026-09-10 15:30:00',
            'created_by' => 91,
            'updated_by' => 92,
            'deleted_by' => 93,
        ]);
        $ana = $this->user('Ana Responsável', 'ana@example.test', 1234567);
        $bia = $this->user('Bia Responsável', 'bia@example.test', 7654321);
        $this->assign($task, $bia);
        $this->assign($task, $ana);

        $tag = Tag::query()->create([
            'name' => ['pt_BR' => 'Integração'],
            'slug' => ['pt_BR' => 'integracao'],
            'type' => Tag::TYPE_TASK,
            'color' => 'badge-primary',
            'description' => 'Metadado interno da tag.',
        ]);
        $task->tags()->attach($tag);

        $expected = [
            'id' => $task->id,
            'title' => 'Documentar integração',
            'description' => $description,
            'status' => [
                'value' => 'DONE',
                'label' => 'Concluída',
            ],
            'priority' => [
                'value' => 2,
                'label' => 'Alta',
            ],
            'start_date' => '2026-09-01',
            'due_date' => '2026-09-12',
            'completed_at' => $task->completed_at->toISOString(),
            'created_at' => $task->created_at->toISOString(),
            'updated_at' => $task->updated_at->toISOString(),
            'assignees' => [
                ['name' => 'Ana Responsável'],
                ['name' => 'Bia Responsável'],
            ],
            'tags' => [[
                'id' => $tag->id,
                'name' => 'Integração',
                'slug' => 'integracao',
            ]],
            'web_url' => route('tasks.show', $task),
        ];
        $token = $this->tokenFor($project, 'viewer');

        $this->withToken($token)
            ->getJson($this->showUrl($project, $task))
            ->assertOk()
            ->assertExactJson(['data' => $expected]);

        $this->withToken($token)
            ->getJson($this->indexUrl($project))
            ->assertOk()
            ->assertJsonPath('data.0', $expected);
    }

    public function test_list_includes_completed_tasks_and_paginates_in_descending_update_and_id_order(): void
    {
        $project = $this->project('Projeto paginado');
        $updatedAt = now()->subDay();

        foreach (range(1, 22) as $number) {
            $this->task($project, [
                'title' => 'Tarefa '.$number,
                'status' => $number === 1 ? 'DONE' : 'NEW',
                'updated_at' => $updatedAt,
            ]);
        }

        $token = $this->tokenFor($project, 'viewer');

        $firstPage = $this->withToken($token)
            ->getJson($this->indexUrl($project))
            ->assertOk()
            ->assertJsonStructure(['data', 'links', 'meta'])
            ->assertJsonPath('meta.total', 22)
            ->assertJsonPath('meta.per_page', 20)
            ->assertJsonPath('meta.last_page', 2);

        $this->assertSame(
            range(22, 3),
            collect($firstPage->json('data'))->pluck('id')->all(),
        );

        $secondPage = $this->withToken($token)
            ->getJson($this->indexUrl($project).'?page=2')
            ->assertOk();

        $this->assertSame(
            [2, 1],
            collect($secondPage->json('data'))->pluck('id')->all(),
        );
        $this->assertSame('DONE', $secondPage->json('data.1.status.value'));
    }

    public function test_per_page_accepts_only_integers_between_one_and_one_hundred(): void
    {
        $project = $this->project('Projeto com tamanho de página');
        foreach (range(1, 3) as $number) {
            $this->task($project, ['title' => 'Tarefa '.$number]);
        }
        $token = $this->tokenFor($project, 'viewer');

        $this->withToken($token)
            ->getJson($this->indexUrl($project).'?per_page=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('meta.per_page', 1);

        $this->withToken($token)
            ->getJson($this->indexUrl($project).'?per_page=100')
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('meta.per_page', 100);

        foreach (['0', '101', 'all'] as $invalidPerPage) {
            $this->withToken($token)
                ->getJson($this->indexUrl($project).'?per_page='.$invalidPerPage)
                ->assertUnprocessable()
                ->assertJsonValidationErrors('per_page');
        }
    }

    public function test_status_filter_accepts_one_or_more_current_task_statuses_and_rejects_invalid_values(): void
    {
        $project = $this->project('Projeto filtrado');
        $new = $this->task($project, ['status' => 'NEW']);
        $inProgress = $this->task($project, ['status' => 'IN_PROGRESS']);
        $done = $this->task($project, ['status' => 'DONE']);
        $token = $this->tokenFor($project, 'viewer');

        $singleStatus = $this->withToken($token)
            ->getJson($this->indexUrl($project).'?status=IN_PROGRESS')
            ->assertOk()
            ->assertJsonCount(1, 'data');
        $this->assertSame([$inProgress->id], collect($singleStatus->json('data'))->pluck('id')->all());

        $multipleStatuses = $this->withToken($token)
            ->getJson($this->indexUrl($project).'?'.http_build_query([
                'status' => ['NEW', 'DONE'],
            ]))
            ->assertOk()
            ->assertJsonCount(2, 'data');
        $this->assertSame(
            [$done->id, $new->id],
            collect($multipleStatuses->json('data'))->pluck('id')->all(),
        );

        $this->withToken($token)
            ->getJson($this->indexUrl($project).'?status=ARCHIVED')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status.0');
    }

    public function test_combined_filters_match_any_requested_tag_and_search_title_or_description(): void
    {
        $project = $this->project('Projeto com filtros');
        $matching = $this->task($project, [
            'title' => 'Planejamento',
            'description' => 'integracao com o serviço externo',
            'status' => 'DONE',
            'priority' => 2,
            'due_date' => '2026-09-12',
        ]);
        $alsoMatching = $this->task($project, [
            'title' => 'INTEGRACAO de dados',
            'status' => 'NEW',
            'priority' => 1,
            'due_date' => '2026-09-10',
        ]);
        $wrongTag = $this->task($project, [
            'title' => 'Integracao sem tag solicitada',
            'status' => 'DONE',
            'priority' => 2,
            'due_date' => '2026-09-12',
        ]);
        $wrongDate = $this->task($project, [
            'title' => 'Integracao fora do intervalo',
            'status' => 'DONE',
            'priority' => 2,
            'due_date' => '2026-09-13',
        ]);
        $firstTag = $this->tag('integracao');
        $secondTag = $this->tag('urgente');
        $otherTag = $this->tag('interno');
        $matching->tags()->attach($firstTag);
        $alsoMatching->tags()->attach($secondTag);
        $wrongTag->tags()->attach($otherTag);
        $wrongDate->tags()->attach($firstTag);

        $response = $this->withToken($this->tokenFor($project, 'viewer'))
            ->getJson($this->indexUrl($project).'?'.http_build_query([
                'status' => ['DONE', 'NEW'],
                'priority' => [1, 2],
                'due_from' => '2026-09-10',
                'due_to' => '2026-09-12',
                'tag' => ['integracao', 'urgente'],
                'search' => 'integracao',
            ]))
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.total', 2);

        $this->assertEqualsCanonicalizing(
            [$matching->id, $alsoMatching->id],
            collect($response->json('data'))->pluck('id')->all(),
        );

        $this->withToken($this->tokenFor($project, 'contributor'))
            ->getJson($this->indexUrl($project).'?tag=desconhecida')
            ->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_invalid_filters_dates_intervals_and_pages_return_laravel_validation_errors(): void
    {
        $project = $this->project('Projeto com validação');
        $this->withToken($this->tokenFor($project, 'viewer'));

        foreach ([
            [['priority' => ['9']], 'priority.0'],
            [['priority' => ['2.5']], 'priority.0'],
            [['tag' => ['']], 'tag.0'],
            [['due_from' => '2026-02-30'], 'due_from'],
            [['due_to' => '2026-09-12T00:00:00Z'], 'due_to'],
            [['due_from' => '2026-09-13', 'due_to' => '2026-09-12'], 'due_to'],
            [['page' => 0], 'page'],
            [['page' => 'abc'], 'page'],
        ] as [$query, $field]) {
            $this->getJson($this->indexUrl($project).'?'.http_build_query($query))
                ->assertUnprocessable()
                ->assertJsonStructure(['message', 'errors'])
                ->assertJsonValidationErrors($field);
        }
    }

    public function test_project_key_reads_the_task_list_and_detail(): void
    {
        $project = $this->project('Projeto em transição');
        $task = $this->task($project);

        $this->withToken($this->tokenFor($project, 'viewer'))
            ->getJson($this->indexUrl($project))
            ->assertOk()
            ->assertJsonPath('data.0.id', $task->id);

        $this->getJson($this->showUrl($project, $task))
            ->assertOk()
            ->assertJsonPath('data.id', $task->id);
    }

    public function test_routes_hide_tasks_from_other_projects_and_soft_deleted_tasks(): void
    {
        $project = $this->project('Projeto permitido');
        $otherProject = $this->project('Projeto alheio');
        $visible = $this->task($project, ['title' => 'Tarefa visível']);
        $deleted = $this->task($project, ['title' => 'Tarefa excluída']);
        $foreign = $this->task($otherProject, ['title' => 'Tarefa alheia']);
        DB::table('tasks')->where('id', $deleted->id)->update(['deleted_at' => now()]);
        $token = $this->tokenFor($project, 'viewer');

        $response = $this->withToken($token)
            ->getJson($this->indexUrl($project))
            ->assertOk()
            ->assertJsonCount(1, 'data');
        $this->assertSame([$visible->id], collect($response->json('data'))->pluck('id')->all());

        $this->withToken($token)
            ->getJson($this->showUrl($project, $foreign))
            ->assertNotFound()
            ->assertJsonStructure(['message']);

        $this->withToken($token)
            ->getJson($this->showUrl($project, $deleted))
            ->assertNotFound()
            ->assertJsonStructure(['message']);

        $this->withToken($token)
            ->getJson($this->indexUrl($otherProject))
            ->assertNotFound()
            ->assertJsonStructure(['message']);

        $this->withToken($token)
            ->getJson($this->indexUrl($project).'/999999')
            ->assertNotFound();
    }

    public function test_list_and_detail_report_a_conflict_when_tasks_module_is_disabled(): void
    {
        $project = $this->project('Projeto sem Tarefas');
        $task = $this->task($project);
        $project->projectModules()->update(['enabled' => false]);
        $token = $this->tokenFor($project, 'viewer');
        $expected = [
            'message' => 'O módulo de Tarefas está desabilitado neste Projeto.',
            'code' => 'tasks_module_disabled',
        ];

        $this->withToken($token)
            ->getJson($this->indexUrl($project))
            ->assertStatus(409)
            ->assertExactJson($expected);

        $this->withToken($token)
            ->getJson($this->showUrl($project, $task))
            ->assertStatus(409)
            ->assertExactJson($expected);
    }

    public function test_foreign_project_with_disabled_tasks_module_is_hidden_before_module_conflict(): void
    {
        $project = $this->project('Projeto autorizado');
        $foreign = $this->project('Projeto com módulo inativo');
        $task = $this->task($foreign);
        $foreign->projectModules()->update(['enabled' => false]);

        $this->withToken($this->tokenFor($project, 'viewer'))
            ->getJson($this->indexUrl($foreign))
            ->assertNotFound();

        $this->getJson($this->showUrl($foreign, $task))
            ->assertNotFound();
    }

    private function project(string $name): Project
    {
        $project = new Project();
        $project->forceFill([
            'name' => $name,
            'slug' => str()->slug($name),
            'status' => 'ACTIVE',
            'permission_inheritance' => 'FULL',
            'visibility' => 'PRIVATE',
        ]);
        $project->save();

        return $project;
    }

    private function task(Project $project, array $attributes = []): Task
    {
        $timestamp = now();
        $id = DB::table('tasks')->insertGetId(array_merge([
            'project_id' => $project->id,
            'title' => 'Tarefa de teste',
            'description' => null,
            'priority' => null,
            'status' => 'NEW',
            'start_date' => null,
            'due_date' => null,
            'completed_at' => null,
            'created_by' => null,
            'updated_by' => null,
            'deleted_by' => null,
            'deleted_via_project' => false,
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
            'deleted_at' => null,
        ], $attributes));

        return Task::query()->findOrFail($id);
    }

    private function user(string $name, string $email, int $codpes): User
    {
        return User::query()->create([
            'name' => $name,
            'email' => $email,
            'codpes' => $codpes,
            'password' => 'secret',
        ]);
    }

    private function assign(Task $task, User $user): void
    {
        DB::table('task_user')->insert([
            'task_id' => $task->id,
            'user_id' => $user->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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

    private function tag(string $slug): Tag
    {
        return Tag::query()->create([
            'name' => ['pt_BR' => $slug],
            'slug' => ['pt_BR' => $slug],
            'type' => Tag::TYPE_TASK,
        ]);
    }

    private function indexUrl(Project $project): string
    {
        return '/api/projects/'.$project->slug.'/tasks';
    }

    private function showUrl(Project $project, Task $task): string
    {
        return $this->indexUrl($project).'/'.$task->id;
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->string('password')->nullable();
            $table->unsignedBigInteger('codpes')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('status');
            $table->text('description')->nullable();
            $table->foreignId('project_type_id')->nullable();
            $table->foreignId('parent_id')->nullable();
            $table->string('visibility')->default('PRIVATE');
            $table->string('permission_inheritance')->default('FULL');
            $table->foreignId('phase_id')->nullable();
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
            $table->timestamp('completed_at')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->foreignId('deleted_by')->nullable();
            $table->boolean('deleted_via_project')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('task_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('task_id');
            $table->foreignId('user_id');
            $table->timestamps();
            $table->unique(['task_id', 'user_id']);
        });

        Schema::create('tags', function (Blueprint $table): void {
            $table->id();
            $table->json('name');
            $table->json('slug');
            $table->string('type')->nullable();
            $table->unsignedInteger('order_column')->nullable();
            $table->string('color')->default('badge-dark');
            $table->text('description')->nullable();
            $table->timestamps();
        });
        Schema::create('taggables', function (Blueprint $table): void {
            $table->foreignId('tag_id');
            $table->morphs('taggable');
            $table->unique(['tag_id', 'taggable_id', 'taggable_type']);
        });

        (require database_path('migrations/2026_07_13_000000_create_uspdev_api_keys_table.php'))->up();
    }
}
