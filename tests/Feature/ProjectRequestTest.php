<?php

namespace Tests\Feature;

use App\Enums\ProjectRequestStatus;
use App\Models\ClientSystem;
use App\Models\Module;
use App\Models\Project;
use App\Models\ProjectRequest;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Uspdev\ApiKeys\Contracts\ApiKeyManager;

class ProjectRequestTest extends TestCase
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
        DB::table('permissions')->insert(collect(array_merge(
            User::$permissoesHierarquia,
            User::$permissoesVinculo,
        ))->unique()->map(fn (string $name): array => [
            'name' => $name,
            'guard_name' => 'senhaunica',
            'created_at' => now(),
            'updated_at' => now(),
        ])->values()->all());
        app(PermissionRegistrar::class)->forgetCachedPermissions();

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

    public function test_contributor_can_create_a_pending_request_and_retrieve_it_at_its_canonical_location(): void
    {
        $project = $this->project('Projeto integrado');
        [$clientSystem, $token] = $this->credentialFor($project, 'contributor');

        $response = $this->withToken($token)
            ->postJson($this->indexUrl($project), [
                'title' => '  Corrigir integração  ',
                'description' => "  Primeira linha\nSegunda linha  ",
                'source_url' => 'https://cliente.example.test/solicitacoes/42',
            ])
            ->assertCreated()
            ->assertJsonPath('data.title', 'Corrigir integração')
            ->assertJsonPath('data.description', "Primeira linha\nSegunda linha")
            ->assertJsonPath('data.status.value', 'pending')
            ->assertJsonPath('data.status.label', 'Pendente')
            ->assertJsonPath('data.response', null)
            ->assertJsonPath('data.evaluated_at', null)
            ->assertJsonPath('data.task', null);

        $requestId = $response->json('data.id');
        $canonicalLocation = $this->showUrl($project, $requestId);

        $response->assertHeader('Location', url($canonicalLocation));

        $detail = $this->withToken($token)
            ->getJson($canonicalLocation)
            ->assertOk()
            ->assertJsonPath('data.id', $requestId)
            ->assertJsonPath('data.source_url', 'https://cliente.example.test/solicitacoes/42')
            ->assertJsonPath('data.web_url', route('projects.requests.show', [$project, $requestId]));

        $this->assertSame($response->json('data'), $detail->json('data'));
        $this->assertSame($project->id, DB::table('project_requests')->value('project_id'));
        $this->assertSame($clientSystem->id, DB::table('project_requests')->value('client_system_id'));
    }

    public function test_request_routes_enforce_the_read_and_create_abilities(): void
    {
        $project = $this->project('Projeto protegido');
        [, $viewerToken] = $this->credentialFor($project, 'viewer');
        [, $contributorToken] = $this->credentialFor($project, 'contributor');

        $this->getJson($this->indexUrl($project))
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Unauthenticated.']);

        $this->withToken($viewerToken)
            ->getJson($this->indexUrl($project))
            ->assertOk();

        $this->withToken($viewerToken)
            ->postJson($this->indexUrl($project), [
                'title' => 'Proposta válida',
                'description' => 'Descrição válida.',
            ])
            ->assertForbidden()
            ->assertExactJson(['message' => 'Forbidden.']);

        $this->withToken($contributorToken)
            ->postJson($this->indexUrl($project), [
                'title' => 'Proposta autorizada',
                'description' => 'Descrição válida.',
            ])
            ->assertCreated();
    }

    public function test_creation_validates_text_limits_and_http_source_urls_without_truncating(): void
    {
        $project = $this->project('Projeto com validação');
        [, $token] = $this->credentialFor($project, 'contributor');
        $maximumTitle = str_repeat('t', 120);
        $maximumDescription = str_repeat('d', 10000);
        Http::fake();

        $this->withToken($token)
            ->postJson($this->indexUrl($project), [
                'title' => $maximumTitle,
                'description' => $maximumDescription,
                'source_url' => 'http://cliente.example.test/origem',
            ])
            ->assertCreated()
            ->assertJsonPath('data.title', $maximumTitle)
            ->assertJsonPath('data.description', $maximumDescription);

        Http::assertNothingSent();

        foreach ([
            [['title' => '  ', 'description' => 'Descrição válida.'], 'title'],
            [['title' => 'ab', 'description' => 'Descrição válida.'], 'title'],
            [['title' => str_repeat('t', 121), 'description' => 'Descrição válida.'], 'title'],
            [['title' => 'Título válido', 'description' => " \n\t "], 'description'],
            [['title' => 'Título válido', 'description' => str_repeat('d', 10001)], 'description'],
            [['title' => 'Título válido', 'description' => 'Descrição válida.', 'source_url' => '/relativa'], 'source_url'],
            [['title' => 'Título válido', 'description' => 'Descrição válida.', 'source_url' => 'ftp://cliente.example.test/origem'], 'source_url'],
            [['title' => 'Título válido', 'description' => 'Descrição válida.', 'source_url' => 'https://example.test/'.str_repeat('u', 2030)], 'source_url'],
        ] as [$payload, $invalidField]) {
            $this->withToken($token)
                ->postJson($this->indexUrl($project), $payload)
                ->assertUnprocessable()
                ->assertJsonValidationErrors($invalidField);
        }
    }

    public function test_creation_rejects_fields_controlled_by_the_server(): void
    {
        $project = $this->project('Projeto com campos protegidos');
        [, $token] = $this->credentialFor($project, 'contributor');

        $controlledFields = [
            'project_id' => 999,
            'project' => ['id' => 999],
            'client_system_id' => 999,
            'client_system' => ['id' => 999],
            'status' => 'accepted',
            'response' => 'Aceita automaticamente.',
            'evaluated_by' => 999,
            'evaluator' => ['id' => 999],
            'evaluated_at' => now()->toISOString(),
            'task_id' => 999,
            'task' => ['id' => 999],
        ];

        $this->withToken($token)
            ->postJson($this->indexUrl($project), [
                'title' => 'Solicitação forjada',
                'description' => 'Tentativa de controlar a avaliação.',
                ...$controlledFields,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(array_keys($controlledFields));

        $this->withToken($token)
            ->postJson($this->indexUrl($project), [
                'title' => 'Campos vazios também são controlados',
                'description' => 'A presença deve ser rejeitada mesmo sem valor.',
                'status' => null,
                'response' => null,
                'evaluated_by' => null,
                'task_id' => null,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['status', 'response', 'evaluated_by', 'task_id']);

        $this->assertSame(0, DB::table('project_requests')->count());
    }

    public function test_client_system_sees_only_its_requests_inside_its_own_project(): void
    {
        $project = $this->project('Projeto permitido');
        $otherProject = $this->project('Projeto alheio');
        [$clientSystem, $token] = $this->credentialFor($project, 'contributor');
        [$otherClientSystem] = $this->credentialFor($project, 'viewer');
        [$foreignClientSystem] = $this->credentialFor($otherProject, 'viewer');
        $visible = $this->projectRequest($project, $clientSystem, ['title' => 'Solicitação visível']);
        $otherOwner = $this->projectRequest($project, $otherClientSystem, ['title' => 'Solicitação de outro sistema']);
        $foreign = $this->projectRequest($otherProject, $foreignClientSystem, ['title' => 'Solicitação de outro Projeto']);

        $response = $this->withToken($token)
            ->getJson($this->indexUrl($project))
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->assertSame([$visible->id], collect($response->json('data'))->pluck('id')->all());

        foreach ([$otherOwner, $foreign] as $hiddenRequest) {
            $this->withToken($token)
                ->getJson($this->showUrl($project, $hiddenRequest->id))
                ->assertNotFound()
                ->assertJsonStructure(['message']);
        }

        $this->withToken($token)
            ->getJson($this->indexUrl($otherProject))
            ->assertNotFound()
            ->assertJsonStructure(['message']);

        $this->withToken($token)
            ->postJson($this->indexUrl($otherProject), [
                'title' => 'Tentativa fora do escopo',
                'description' => 'Esta Solicitação não deve ser criada.',
            ])
            ->assertNotFound()
            ->assertJsonStructure(['message']);
    }

    public function test_list_is_paginated_in_descending_creation_order_and_filters_by_status(): void
    {
        $project = $this->project('Projeto com histórico');
        [$clientSystem, $token] = $this->credentialFor($project, 'viewer');

        foreach (range(1, 22) as $number) {
            $status = match ($number) {
                1 => ProjectRequestStatus::REJECTED,
                2 => ProjectRequestStatus::ACCEPTED,
                default => ProjectRequestStatus::PENDING,
            };

            $this->projectRequest($project, $clientSystem, [
                'title' => 'Solicitação '.$number,
                'status' => $status,
            ]);
        }

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
        $this->assertSame([2, 1], collect($secondPage->json('data'))->pluck('id')->all());

        $filtered = $this->withToken($token)
            ->getJson($this->indexUrl($project).'?'.http_build_query([
                'status' => ['accepted', 'rejected'],
                'per_page' => 100,
            ]))
            ->assertOk()
            ->assertJsonPath('meta.per_page', 100)
            ->assertJsonCount(2, 'data');
        $this->assertSame([2, 1], collect($filtered->json('data'))->pluck('id')->all());

        $singleStatus = $this->withToken($token)
            ->getJson($this->indexUrl($project).'?status=accepted&per_page=1')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonCount(1, 'data');
        $this->assertSame('accepted', $singleStatus->json('data.0.status.value'));

        foreach (['0', '101', 'all'] as $invalidPerPage) {
            $this->withToken($token)
                ->getJson($this->indexUrl($project).'?per_page='.$invalidPerPage)
                ->assertUnprocessable()
                ->assertJsonValidationErrors('per_page');
        }

        $this->withToken($token)
            ->getJson($this->indexUrl($project).'?status=cancelled')
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status.0');
    }

    public function test_api_representation_exposes_final_result_without_evaluator_identity(): void
    {
        $project = $this->project('Projeto com avaliação');
        [$clientSystem, $token] = $this->credentialFor($project, 'viewer');
        $evaluator = User::query()->create([
            'name' => 'Avaliadora interna',
            'email' => 'avaliadora@example.test',
            'password' => 'secret',
        ]);
        $task = $this->task($project, 'Tarefa incorporada');
        $evaluatedAt = now()->subHour();
        $accepted = $this->projectRequest($project, $clientSystem, [
            'title' => 'Melhorar integração',
            'description' => "Descrição integral\nsem Markdown processado.",
            'source_url' => 'https://cliente.example.test/pedidos/9',
            'status' => ProjectRequestStatus::ACCEPTED,
            'response' => 'Proposta incorporada ao planejamento.',
            'evaluated_by' => $evaluator->id,
            'evaluated_at' => $evaluatedAt,
            'task_id' => $task->id,
        ]);

        $expected = [
            'id' => $accepted->id,
            'title' => 'Melhorar integração',
            'description' => "Descrição integral\nsem Markdown processado.",
            'source_url' => 'https://cliente.example.test/pedidos/9',
            'status' => [
                'value' => 'accepted',
                'label' => 'Aceita',
            ],
            'response' => 'Proposta incorporada ao planejamento.',
            'created_at' => $accepted->created_at->toISOString(),
            'updated_at' => $accepted->updated_at->toISOString(),
            'evaluated_at' => $accepted->evaluated_at->toISOString(),
            'task' => [
                'id' => $task->id,
                'title' => 'Tarefa incorporada',
                'web_url' => route('tasks.show', $task),
            ],
            'web_url' => route('projects.requests.show', [$project, $accepted]),
        ];

        $this->withToken($token)
            ->getJson($this->showUrl($project, $accepted->id))
            ->assertOk()
            ->assertExactJson(['data' => $expected])
            ->assertJsonMissing(['evaluated_by' => $evaluator->id])
            ->assertJsonMissing(['evaluator' => ['name' => 'Avaliadora interna']]);

        $this->withToken($token)
            ->getJson($this->indexUrl($project))
            ->assertOk()
            ->assertJsonPath('data.0', $expected);
    }

    public function test_disabled_tasks_module_blocks_creation_but_keeps_existing_requests_readable(): void
    {
        $project = $this->project('Projeto sem Tarefas');
        [$clientSystem, $token] = $this->credentialFor($project, 'contributor');
        $existing = $this->projectRequest($project, $clientSystem);
        $project->projectModules()->update(['enabled' => false]);

        $this->withToken($token)
            ->postJson($this->indexUrl($project), [
                'title' => 'Nova Solicitação',
                'description' => 'Não deve ser criada com o módulo desabilitado.',
            ])
            ->assertStatus(409)
            ->assertExactJson([
                'message' => 'O módulo de Tarefas está desabilitado neste Projeto.',
                'code' => 'tasks_module_disabled',
            ]);

        $this->withToken($token)
            ->getJson($this->indexUrl($project))
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->withToken($token)
            ->getJson($this->showUrl($project, $existing->id))
            ->assertOk()
            ->assertJsonPath('data.id', $existing->id);
    }

    public function test_removing_the_evaluator_preserves_the_final_request_history(): void
    {
        $project = $this->project('Projeto com histórico preservado');
        [$clientSystem] = $this->credentialFor($project, 'viewer');
        $evaluator = User::query()->create([
            'name' => 'Avaliadora removida',
            'email' => 'removida@example.test',
            'password' => 'secret',
        ]);
        $evaluatedAt = now()->subDay();
        $projectRequest = $this->projectRequest($project, $clientSystem, [
            'status' => ProjectRequestStatus::REJECTED,
            'response' => 'A proposta não será incorporada.',
            'evaluated_by' => $evaluator->id,
            'evaluated_at' => $evaluatedAt,
        ]);

        $evaluator->delete();
        $projectRequest->refresh();

        $this->assertNull($projectRequest->evaluated_by);
        $this->assertSame(ProjectRequestStatus::REJECTED, $projectRequest->status);
        $this->assertSame('A proposta não será incorporada.', $projectRequest->response);
        $this->assertNotNull($projectRequest->evaluated_at);
    }

    public function test_a_task_can_be_the_result_of_only_one_request(): void
    {
        $project = $this->project('Projeto com Tarefa única');
        [$clientSystem] = $this->credentialFor($project, 'viewer');
        $task = $this->task($project, 'Tarefa resultante');
        $this->projectRequest($project, $clientSystem, [
            'status' => ProjectRequestStatus::ACCEPTED,
            'task_id' => $task->id,
        ]);

        $this->expectException(QueryException::class);

        $this->projectRequest($project, $clientSystem, [
            'status' => ProjectRequestStatus::ACCEPTED,
            'task_id' => $task->id,
        ]);
    }

    public function test_local_triage_members_can_list_and_view_requests_as_safe_plain_text(): void
    {
        $administrator = $this->user('Administradora local');
        $contributor = $this->user('Contribuidor local');
        $project = $this->project('Projeto em triagem');
        $project->users()->attach($administrator, ['role' => 'ADMIN']);
        $project->users()->attach($contributor, ['role' => 'CONTRIBUTOR']);
        [$firstClientSystem] = $this->credentialFor($project, 'viewer');
        [$secondClientSystem] = $this->credentialFor($project, 'viewer');
        $pending = $this->projectRequest($project, $firstClientSystem, [
            'title' => 'Revisar conteúdo externo',
            'description' => "<script>alert('não executar')</script>\nSegunda linha.",
            'source_url' => 'https://cliente.example.test/origem/15',
        ]);
        $this->projectRequest($project, $secondClientSystem, [
            'title' => 'Outra proposta pendente',
        ]);
        $task = $this->task($project, 'Tarefa já incorporada');
        $accepted = $this->projectRequest($project, $firstClientSystem, [
            'title' => 'Proposta aceita',
            'status' => ProjectRequestStatus::ACCEPTED,
            'response' => 'Será executada.',
            'evaluated_at' => now(),
            'task_id' => $task->id,
        ]);

        foreach ([$administrator, $contributor] as $triageMember) {
            $index = $this->actingAs($triageMember)
                ->get(route('projects.requests.index', $project))
                ->assertOk()
                ->assertSee('Revisar conteúdo externo')
                ->assertSee('Outra proposta pendente')
                ->assertSee('Proposta aceita')
                ->assertSee($firstClientSystem->name)
                ->assertSee($secondClientSystem->name);

            $this->assertMatchesRegularExpression(
                '/data-pending-requests-count[^>]*>\s*2\s*<\/span>.*data-project-requests-title[^>]*>\s*Solicitações/s',
                $index->getContent(),
            );

            $this->actingAs($triageMember)
                ->get(route('projects.requests.show', [$project, $pending]))
                ->assertOk()
                ->assertSee('&lt;script&gt;alert(&#039;não executar&#039;)&lt;/script&gt;', false)
                ->assertDontSee("<script>alert('não executar')</script>", false)
                ->assertSee('Segunda linha.')
                ->assertSee('https://cliente.example.test/origem/15');

            $this->actingAs($triageMember)
                ->get(route('projects.requests.show', [$project, $accepted]))
                ->assertOk()
                ->assertSee('Será executada.')
                ->assertSee(route('tasks.show', $task));
        }
    }

    public function test_internal_queue_rejects_viewers_inherited_access_and_unlinked_global_administrators(): void
    {
        $viewer = $this->user('Visualizadora local');
        $inheritedAdministrator = $this->user('Administrador herdado');
        $globalAdministrator = $this->globalAdministrator('Administradora global');
        $parent = $this->project('Projeto pai');
        $project = $this->project('Subprojeto', $parent);
        $project->users()->attach($viewer, ['role' => 'VIEWER']);
        $parent->users()->attach($inheritedAdministrator, ['role' => 'ADMIN']);
        [$clientSystem] = $this->credentialFor($project, 'viewer');
        $projectRequest = $this->projectRequest($project, $clientSystem);

        foreach ([$viewer, $inheritedAdministrator, $globalAdministrator] as $unauthorizedUser) {
            $this->actingAs($unauthorizedUser)
                ->get(route('projects.requests.index', $project))
                ->assertForbidden();

            $this->actingAs($unauthorizedUser)
                ->get(route('projects.requests.show', [$project, $projectRequest]))
                ->assertForbidden();
        }
    }

    public function test_internal_detail_hides_a_request_combined_with_another_project(): void
    {
        $administrator = $this->user('Administradora dos Projetos');
        $project = $this->project('Primeiro Projeto');
        $otherProject = $this->project('Segundo Projeto');
        $project->users()->attach($administrator, ['role' => 'ADMIN']);
        $otherProject->users()->attach($administrator, ['role' => 'ADMIN']);
        [$clientSystem] = $this->credentialFor($project, 'viewer');
        $projectRequest = $this->projectRequest($project, $clientSystem);

        $this->actingAs($administrator)
            ->get(route('projects.requests.show', [$otherProject, $projectRequest]))
            ->assertNotFound();
    }

    public function test_tasks_page_shows_the_pending_request_queue_only_to_local_triage_members(): void
    {
        $contributor = $this->user('Contribuidor da fila');
        $viewer = $this->user('Visualizadora da fila');
        $project = $this->project('Projeto com fila');
        $project->users()->attach($contributor, ['role' => 'CONTRIBUTOR']);
        $project->users()->attach($viewer, ['role' => 'VIEWER']);
        [$clientSystem] = $this->credentialFor($project, 'viewer');
        $this->projectRequest($project, $clientSystem);
        $this->projectRequest($project, $clientSystem);

        $index = $this->actingAs($contributor)
            ->get(route('projects.tasks.index', $project))
            ->assertOk()
            ->assertSee(route('projects.requests.index', $project));

        $this->assertMatchesRegularExpression(
            '/data-pending-requests-count[^>]*>\s*2\s*<\/span>.*data-project-requests-title[^>]*>\s*Solicitações/s',
            $index->getContent(),
        );

        $this->actingAs($viewer)
            ->get(route('projects.tasks.index', $project))
            ->assertOk()
            ->assertDontSee(route('projects.requests.index', $project));
    }

    public function test_internal_queue_remains_available_when_tasks_module_is_disabled(): void
    {
        $contributor = $this->user('Contribuidor do histórico');
        $project = $this->project('Projeto com módulo desabilitado');
        $project->users()->attach($contributor, ['role' => 'CONTRIBUTOR']);
        [$clientSystem] = $this->credentialFor($project, 'viewer');
        $projectRequest = $this->projectRequest($project, $clientSystem);
        $project->projectModules()->update(['enabled' => false]);

        $this->actingAs($contributor)
            ->get(route('projects.requests.index', $project))
            ->assertOk()
            ->assertSee($projectRequest->title)
            ->assertSee('data-project-requests-menu', false);

        $this->actingAs($contributor)
            ->get(route('projects.requests.show', [$project, $projectRequest]))
            ->assertOk()
            ->assertSee($projectRequest->description);
    }

    /**
     * @return array{ClientSystem, string}
     */
    private function credentialFor(Project $project, string $role): array
    {
        $clientSystem = $project->clientSystems()->create([
            'name' => 'Sistema '.$project->id.' '.$role.' '.str()->random(6),
        ]);

        $token = app(ApiKeyManager::class)->create(
            $clientSystem,
            'Credencial de teste',
            'integration',
            $role,
        )->plainTextToken();

        return [$clientSystem, $token];
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

        return $project;
    }

    private function user(string $name): User
    {
        return User::query()->create([
            'name' => $name,
            'email' => str()->slug($name).'@example.test',
            'password' => 'secret',
        ]);
    }

    private function globalAdministrator(string $name): User
    {
        $user = $this->user($name);
        $user->givePermissionTo(Permission::findByName('admin', 'senhaunica'));

        return $user;
    }

    private function projectRequest(
        Project $project,
        ClientSystem $clientSystem,
        array $attributes = [],
    ): ProjectRequest {
        $projectRequest = new ProjectRequest();
        $projectRequest->forceFill(array_merge([
            'project_id' => $project->id,
            'client_system_id' => $clientSystem->id,
            'title' => 'Solicitação de teste',
            'description' => 'Descrição da Solicitação.',
            'status' => ProjectRequestStatus::PENDING,
        ], $attributes));
        $projectRequest->save();

        return $projectRequest;
    }

    private function task(Project $project, string $title): Task
    {
        $task = new Task();
        $task->forceFill([
            'project_id' => $project->id,
            'title' => $title,
            'status' => 'NEW',
        ]);
        $task->save();

        return $task;
    }

    private function indexUrl(Project $project): string
    {
        return '/api/projects/'.$project->slug.'/requests';
    }

    private function showUrl(Project $project, int $requestId): string
    {
        return $this->indexUrl($project).'/'.$requestId;
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->string('password')->nullable();
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

        Schema::create('project_user', function (Blueprint $table): void {
            $table->foreignId('project_id');
            $table->foreignId('user_id');
            $table->string('role');
            $table->boolean('pinned')->default(false);
            $table->timestamps();
        });

        Schema::create('model_has_permissions', function (Blueprint $table): void {
            $table->foreignId('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['permission_id', 'model_id', 'model_type']);
        });

        Schema::create('model_has_roles', function (Blueprint $table): void {
            $table->foreignId('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['role_id', 'model_id', 'model_type']);
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
            $table->primary(['permission_id', 'role_id']);
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

        Schema::create('client_systems', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id');
            $table->string('name', 120);
            $table->text('description')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->timestamps();
            $table->unique(['project_id', 'name']);
        });

        (require database_path('migrations/2026_07_13_000000_create_uspdev_api_keys_table.php'))->up();
        (require database_path('migrations/2026_09_14_010000_create_project_requests_table.php'))->up();
    }
}
