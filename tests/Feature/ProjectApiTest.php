<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\Phase;
use App\Models\Project;
use App\Models\ProjectType;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Uspdev\ApiKeys\Contracts\ApiKeyManager;
use Uspdev\ApiKeys\Dto\CreatedApiKeyDto;

class ProjectApiTest extends TestCase
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
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');

        parent::tearDown();
    }

    public function test_viewer_key_can_read_its_project_through_the_bearer_header(): void
    {
        $project = $this->project('Projeto integrado');
        $token = $this->tokenFor($project, 'viewer');

        $this->withToken($token)
            ->getJson('/api/projects/'.$project->slug)
            ->assertOk()
            ->assertJsonPath('data.id', $project->id);
    }

    public function test_route_requires_an_active_bearer_key_with_projects_read_ability(): void
    {
        $project = $this->project('Projeto protegido');

        $this->getJson('/api/projects/'.$project->slug)
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Unauthenticated.']);

        $queryOnlyToken = $this->tokenFor($project, 'viewer');

        $this->getJson('/api/projects/'.$project->slug.'?'.http_build_query([
            'api_key' => $queryOnlyToken,
        ]))
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Unauthenticated.']);

        $this->withToken('invalid')
            ->getJson('/api/projects/'.$project->slug)
            ->assertUnauthorized()
            ->assertExactJson(['message' => 'Unauthenticated.']);

        $expired = $this->credentialFor($project, 'viewer');
        $expired->apiKey->forceFill(['expires_at' => now()->subMinute()])->save();

        $this->withToken($expired->plainTextToken())
            ->getJson('/api/projects/'.$project->slug)
            ->assertUnauthorized();

        $revoked = $this->credentialFor($project, 'viewer');
        app(ApiKeyManager::class)->revoke($revoked->apiKey);

        $this->withToken($revoked->plainTextToken())
            ->getJson('/api/projects/'.$project->slug)
            ->assertUnauthorized();

        $withoutAbility = $this->tokenFor($project, 'unknown');

        $this->withToken($withoutAbility)
            ->getJson('/api/projects/'.$project->slug)
            ->assertForbidden()
            ->assertExactJson(['message' => 'Forbidden.']);

        $this->withToken($this->tokenFor($project, 'contributor'))
            ->getJson('/api/projects/'.$project->slug)
            ->assertOk();
    }

    public function test_key_cannot_read_a_project_outside_its_owner_scope(): void
    {
        $allowedProject = $this->project('Projeto permitido');
        $otherProject = $this->project('Projeto alheio');
        $token = $this->tokenFor($allowedProject, 'viewer');

        $this->withToken($token)
            ->getJson('/api/projects/'.$otherProject->slug)
            ->assertNotFound()
            ->assertJsonStructure(['message'])
            ->assertJsonMissing(['id' => $otherProject->id]);
    }

    public function test_missing_and_deleted_projects_are_not_readable(): void
    {
        $project = $this->project('Projeto da credencial');
        $token = $this->tokenFor($project, 'viewer');

        $this->withToken($token)
            ->getJson('/api/projects/projeto-inexistente')
            ->assertNotFound();

        $slug = $project->slug;
        $project->delete();
        $this->withToken($token)
            ->getJson('/api/projects/'.$slug)
            ->assertNotFound();
    }

    public function test_purpose_is_descriptive_and_does_not_change_project_read_access(): void
    {
        $project = $this->project('Projeto das finalidades');

        foreach (['integration', 'ai'] as $purpose) {
            $credential = app(ApiKeyManager::class)->create(
                $project,
                'Leitura '.$purpose,
                $purpose,
                'viewer',
            );

            $this->withToken($credential->plainTextToken())
                ->getJson('/api/projects/'.$project->slug)
                ->assertOk()
                ->assertJsonPath('data.id', $project->id);
        }
    }

    public function test_project_resource_exposes_only_the_documented_context(): void
    {
        $parent = $this->project('Programa institucional');
        $projectType = ProjectType::query()->create([
            'name' => 'Desenvolvimento',
            'slug' => 'desenvolvimento',
            'description' => 'Configuração interna do tipo.',
            'enabled' => true,
        ]);
        $phase = Phase::query()->create([
            'name' => 'Produção',
            'slug' => 'production',
            'description' => 'Configuração interna da fase.',
            'is_active' => true,
        ]);
        $project = $this->project('Portal de serviços');
        $project->forceFill([
            'description' => "## Contexto\n\nDescrição em **Markdown**.",
            'project_type_id' => $projectType->id,
            'parent_id' => $parent->id,
            'phase_id' => $phase->id,
            'created_by' => 91,
            'updated_by' => 92,
            'deleted_by' => 93,
        ])->save();

        $tag = Tag::query()->create([
            'name' => ['pt_BR' => 'Prioridade institucional'],
            'slug' => ['pt_BR' => 'prioridade-institucional'],
            'type' => Tag::TYPE_PROJECT,
            'color' => 'badge-primary',
        ]);
        $project->tags()->attach($tag);

        $tasks = Module::query()->create([
            'name' => 'Tarefas',
            'slug' => 'tasks',
            'description' => 'Configuração interna do módulo.',
        ]);
        $meetings = Module::query()->create([
            'name' => 'Reuniões',
            'slug' => 'meetings',
        ]);
        $project->projectModules()->create([
            'module_id' => $tasks->id,
            'enabled' => false,
        ]);
        $project->projectModules()->create([
            'module_id' => $meetings->id,
            'enabled' => true,
        ]);

        $response = $this->withToken($this->tokenFor($project, 'viewer'))
            ->getJson('/api/projects/'.$project->slug)
            ->assertOk();

        $response->assertExactJson([
            'data' => [
                'id' => $project->id,
                'slug' => 'portal-de-servicos',
                'name' => 'Portal de serviços',
                'description' => "## Contexto\n\nDescrição em **Markdown**.",
                'status' => [
                    'value' => 'ACTIVE',
                    'label' => 'Ativo',
                ],
                'type' => [
                    'id' => $projectType->id,
                    'slug' => 'desenvolvimento',
                    'name' => 'Desenvolvimento',
                ],
                'phase' => [
                    'id' => $phase->id,
                    'slug' => 'production',
                    'name' => 'Produção',
                ],
                'parent' => [
                    'id' => $parent->id,
                    'slug' => 'programa-institucional',
                    'name' => 'Programa institucional',
                ],
                'tags' => [[
                    'id' => $tag->id,
                    'name' => 'Prioridade institucional',
                    'slug' => 'prioridade-institucional',
                ]],
                'modules' => [
                    'enabled' => ['meetings'],
                ],
                'web_url' => route('projects.show', $project),
                'created_at' => $project->created_at->toISOString(),
                'updated_at' => $project->updated_at->toISOString(),
            ],
        ]);
    }

    public function test_project_read_reports_enabled_modules_without_a_redundant_flag(): void
    {
        $tasks = Module::query()->create([
            'name' => 'Tarefas',
            'slug' => 'tasks',
        ]);
        $project = $this->project('Projeto modular');
        $token = $this->tokenFor($project, 'viewer');

        $this->withToken($token)
            ->getJson('/api/projects/'.$project->slug)
            ->assertOk()
            ->assertJsonPath('data.modules.enabled.0', 'tasks')
            ->assertJsonMissingPath('data.modules.tasks_enabled');

        $project->projectModules()
            ->where('module_id', $tasks->id)
            ->update(['enabled' => false]);

        $this->withToken($token)
            ->getJson('/api/projects/'.$project->slug)
            ->assertOk()
            ->assertJsonPath('data.modules.enabled', [])
            ->assertJsonMissingPath('data.modules.tasks_enabled');
    }

    public function test_slug_warning_mentions_api_urls_and_is_highlighted_for_integrated_projects(): void
    {
        $administrator = User::query()->create([
            'name' => 'Administradora local',
            'email' => 'admin@example.test',
            'password' => 'secret',
        ]);
        $projectWithoutIntegration = $this->project('Projeto sem integração');
        $projectWithIntegration = $this->project('Projeto com integração');
        $projectWithoutIntegration->users()->attach($administrator, ['role' => 'ADMIN']);
        $projectWithIntegration->users()->attach($administrator, ['role' => 'ADMIN']);
        app(ApiKeyManager::class)->create(
            $projectWithIntegration,
            'Integração externa',
            'integration',
            'viewer',
        );

        $this->actingAs($administrator)
            ->get(route('projects.settings', $projectWithoutIntegration))
            ->assertOk()
            ->assertSee('Alterar o slug quebrará links antigos, inclusive as URLs da API.')
            ->assertSee('data-api-url-warning class="text-muted', false)
            ->assertDontSee('data-api-url-warning class="alert alert-warning', false);

        $this->actingAs($administrator)
            ->get(route('projects.settings', $projectWithIntegration))
            ->assertOk()
            ->assertSee('Alterar o slug quebrará links antigos, inclusive as URLs da API.')
            ->assertSee('data-api-url-warning class="alert alert-warning', false);
    }

    public function test_project_route_keeps_the_api_limit_of_sixty_requests_per_ip(): void
    {
        $project = $this->project('Projeto limitado');
        $this->withToken($this->tokenFor($project, 'viewer'));

        for ($requestNumber = 1; $requestNumber <= 60; $requestNumber++) {
            $this->getJson('/api/projects/'.$project->slug)->assertOk();
        }

        $this->getJson('/api/projects/'.$project->slug)
            ->assertStatus(429)
            ->assertJsonStructure(['message']);
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

    private function tokenFor(Project $project, string $role): string
    {
        return $this->credentialFor($project, $role)->plainTextToken();
    }

    private function credentialFor(Project $project, string $role): CreatedApiKeyDto
    {
        return app(ApiKeyManager::class)->create(
            $project,
            'Credencial de teste',
            'integration',
            $role,
        );
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

        Schema::create('project_types', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });

        Schema::create('phases', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_initial')->default(false);
            $table->boolean('is_final')->default(false);
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
            $table->boolean('deleted_via_project')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('project_type_modules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_type_id');
            $table->foreignId('module_id');
            $table->boolean('enabled')->default(true);
            $table->boolean('required')->default(false);
            $table->boolean('editable')->default(true);
            $table->json('config')->nullable();
            $table->timestamps();
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
        Schema::create('project_user', function (Blueprint $table): void {
            $table->foreignId('project_id');
            $table->foreignId('user_id');
            $table->string('role');
            $table->boolean('pinned')->default(false);
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
            $table->primary(['permission_id', 'model_id', 'model_type']);
        });
        Schema::create('model_has_roles', function (Blueprint $table): void {
            $table->foreignId('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['role_id', 'model_id', 'model_type']);
        });
        Schema::create('role_has_permissions', function (Blueprint $table): void {
            $table->foreignId('permission_id');
            $table->foreignId('role_id');
            $table->primary(['permission_id', 'role_id']);
        });

        (require database_path('migrations/2026_07_13_000000_create_uspdev_api_keys_table.php'))->up();

        DB::table('permissions')->insert(collect(array_merge(
            User::$permissoesHierarquia,
            User::$permissoesVinculo,
        ))->map(fn (string $name): array => [
            'name' => $name,
            'guard_name' => 'senhaunica',
            'created_at' => now(),
            'updated_at' => now(),
        ])->all());
    }
}
