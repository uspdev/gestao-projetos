<?php

namespace Tests\Feature;

use App\Morphs\ApiKeyOwnerMap;
use App\Models\ClientSystem;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Uspdev\ApiKeys\Contracts\ApiKeyManager;
use Uspdev\ApiKeys\Models\ApiKey;

class ClientSystemApiKeyManagementTest extends TestCase
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

    public function test_configuration_owner_alias_and_role_abilities_match_the_contract(): void
    {
        $clientSystem = new ClientSystem();

        $this->assertSame(ClientSystem::class, config('api-keys.owners.client-system'));
        $this->assertSame([
            'client-system' => ClientSystem::class,
        ], ApiKeyOwnerMap::morphMap());
        $this->assertFalse(config('api-keys.query_parameter.enabled'));
        $this->assertSame([
            'integration' => 'Integração',
            'ai' => 'IA',
        ], config('api-keys.interface.purposes'));
        $this->assertSame([
            'viewer' => 'Visualizador',
            'contributor' => 'Contribuidor',
        ], config('api-keys.interface.roles'));
        $this->assertSame('client-system', $clientSystem->getMorphClass());
        $this->assertSame([
            'projects.read',
            'tasks.read',
        ], $clientSystem->abilities('viewer'));
        $this->assertSame([
            'projects.read',
            'tasks.read',
        ], $clientSystem->abilities('contributor'));
        $this->assertSame([], $clientSystem->abilities('administrator'));
        $this->assertSame([], $clientSystem->abilities('unknown'));
    }

    public function test_local_administrator_can_create_and_edit_a_client_system_with_auditing(): void
    {
        $administrator = $this->user('Administradora local');
        $project = $this->project('Projeto integrado');
        $project->users()->attach($administrator, ['role' => 'ADMIN']);

        $this->actingAs($administrator)
            ->post(route('projects.client-systems.store', $project), [
                'name' => 'Chamados',
                'description' => "Origem das solicitações\nexternas.",
                '_client_system_form' => 'create',
            ])
            ->assertRedirect(route('projects.settings', $project).'#project-integrations-settings');

        $clientSystem = ClientSystem::query()->firstOrFail();

        $this->assertSame($project->id, $clientSystem->project_id);
        $this->assertSame($administrator->id, $clientSystem->created_by);
        $this->assertSame($administrator->id, $clientSystem->updated_by);

        $this->actingAs($administrator)
            ->patch(route('projects.client-systems.update', [$project, $clientSystem]), [
                'name' => 'Central de chamados',
                'description' => 'Finalidade atualizada.',
                '_client_system_form' => (string) $clientSystem->id,
            ])
            ->assertRedirect(route('projects.settings', $project).'#project-integrations-settings');

        $this->assertDatabaseHas('client_systems', [
            'id' => $clientSystem->id,
            'project_id' => $project->id,
            'name' => 'Central de chamados',
            'description' => 'Finalidade atualizada.',
            'created_by' => $administrator->id,
            'updated_by' => $administrator->id,
        ]);
    }

    public function test_client_system_validation_enforces_limits_and_project_scoped_uniqueness(): void
    {
        $administrator = $this->user('Administradora');
        $firstProject = $this->project('Primeiro projeto');
        $secondProject = $this->project('Segundo projeto');
        $firstProject->users()->attach($administrator, ['role' => 'ADMIN']);
        $secondProject->users()->attach($administrator, ['role' => 'ADMIN']);
        $firstProject->clientSystems()->create(['name' => 'Integração USP']);

        $this->actingAs($administrator)
            ->from(route('projects.settings', $firstProject))
            ->post(route('projects.client-systems.store', $firstProject), [
                'name' => 'Integração USP',
                'description' => str_repeat('a', 1001),
                '_client_system_form' => 'create',
            ])
            ->assertRedirect(route('projects.settings', $firstProject))
            ->assertSessionHasErrors(['name', 'description']);

        $this->actingAs($administrator)
            ->post(route('projects.client-systems.store', $secondProject), [
                'name' => 'Integração USP',
                'description' => null,
                '_client_system_form' => 'create',
            ])
            ->assertRedirect(route('projects.settings', $secondProject).'#project-integrations-settings');

        $this->assertDatabaseCount('client_systems', 2);

        $this->actingAs($administrator)
            ->post(route('projects.client-systems.store', $firstProject), [
                'name' => 'ab',
                '_client_system_form' => 'create',
            ])
            ->assertSessionHasErrors('name');
    }

    public function test_only_local_or_global_administrators_can_manage_client_systems(): void
    {
        $localAdministrator = $this->user('Administradora local');
        $contributor = $this->user('Contribuidor');
        $viewer = $this->user('Visualizador');
        $inheritedAdministrator = $this->user('Administrador herdado');
        $globalAdministrator = $this->globalAdministrator('Administradora global');
        $parent = $this->project('Projeto pai');
        $project = $this->project('Subprojeto', $parent);
        $project->users()->attach($localAdministrator, ['role' => 'ADMIN']);
        $project->users()->attach($contributor, ['role' => 'CONTRIBUTOR']);
        $project->users()->attach($viewer, ['role' => 'VIEWER']);
        $parent->users()->attach($inheritedAdministrator, ['role' => 'ADMIN']);

        foreach ([$contributor, $viewer, $inheritedAdministrator] as $unauthorizedUser) {
            $this->actingAs($unauthorizedUser)
                ->post(route('projects.client-systems.store', $project), [
                    'name' => 'Sistema recusado',
                ])
                ->assertForbidden();
        }

        $this->actingAs($globalAdministrator)
            ->post(route('projects.client-systems.store', $project), [
                'name' => 'Sistema global',
            ])
            ->assertRedirect(route('projects.settings', $project).'#project-integrations-settings');

        $clientSystem = ClientSystem::query()->firstOrFail();

        $this->actingAs($contributor)
            ->post(route('api-keys.keys.store', ['client-system', $clientSystem]), [
                'name' => 'Chave recusada',
                'purpose' => 'integration',
                'role' => 'viewer',
            ])
            ->assertForbidden();

        $this->actingAs($globalAdministrator)
            ->post(route('api-keys.keys.store', ['client-system', $clientSystem]), [
                'name' => 'Chave global',
                'purpose' => 'integration',
                'role' => 'viewer',
            ])
            ->assertRedirect();

        $this->actingAs($localAdministrator)
            ->get(route('projects.client-systems.api-keys', [$project, $clientSystem]))
            ->assertOk();

        $this->actingAs($globalAdministrator)
            ->get(route('projects.client-systems.api-keys', [$project, $clientSystem]))
            ->assertOk();

        foreach ([$contributor, $viewer, $inheritedAdministrator] as $unauthorizedUser) {
            $this->actingAs($unauthorizedUser)
                ->get(route('projects.client-systems.api-keys', [$project, $clientSystem]))
                ->assertForbidden();
        }
    }

    public function test_key_manager_uses_package_routes_and_preserves_one_time_secret_delivery(): void
    {
        $administrator = $this->user('Administradora');
        $project = $this->project('Projeto com chaves');
        $project->users()->attach($administrator, ['role' => 'ADMIN']);
        $clientSystem = $project->clientSystems()->create(['name' => 'Agente externo']);
        $managerUrl = route('projects.client-systems.api-keys', [$project, $clientSystem]);

        $this->actingAs($administrator)
            ->get($managerUrl)
            ->assertOk()
            ->assertSee('API Keys')
            ->assertSee('value="integration"', false)
            ->assertSee('value="ai"', false)
            ->assertSee('value="viewer"', false)
            ->assertSee('value="contributor"', false)
            ->assertDontSee('value="administrator"', false)
            ->assertDontSee('value="collaborator"', false);

        $this->actingAs($administrator)
            ->post(route('api-keys.keys.store', ['client-system', $clientSystem]), [
                'name' => 'Leitura da IA',
                'purpose' => 'ai',
                'role' => 'viewer',
                'expires_at' => now()->addDays(2)->toDateString(),
                '_api_keys_owner_alias' => 'client-system',
                '_api_keys_owner_key' => (string) $clientSystem->id,
                '_api_keys_operation' => 'create',
                '_api_keys_id' => '',
            ])
            ->assertRedirect();

        $apiKey = ApiKey::query()->firstOrFail();

        $this->assertSame('client-system', $apiKey->owner_type);
        $this->assertSame($clientSystem->id, $apiKey->owner_id);
        $this->assertSame('ai', $apiKey->purpose);
        $this->assertSame('viewer', $apiKey->role);
        $this->assertNotNull($apiKey->expires_at);
        $this->assertSame($administrator->id, $apiKey->created_by);
        $this->assertTrue($apiKey->allows('projects.read'));
        $this->assertTrue($apiKey->allows('tasks.read'));
        $this->assertFalse($apiKey->allows('requests.read'));
        $this->assertFalse($apiKey->allows('requests.create'));
        $this->assertFalse($apiKey->allows('*'));

        $firstDisplay = $this->get($managerUrl)
            ->assertOk()
            ->assertSee('Ela será exibida apenas uma vez.');
        $this->assertSame(
            1,
            preg_match('/gpp_[A-Z0-9]{6}\.[A-Za-z0-9_-]+/', $firstDisplay->getContent(), $matches),
        );
        $plainTextToken = $matches[0];

        $this->get($managerUrl)
            ->assertOk()
            ->assertDontSee($plainTextToken);
    }

    public function test_integrations_section_lists_safe_plain_text_and_only_supported_actions(): void
    {
        $administrator = $this->user('Administradora');
        $project = $this->project('Projeto com integração');
        $project->users()->attach($administrator, ['role' => 'ADMIN']);
        $clientSystem = $project->clientSystems()->create([
            'name' => 'Sistema externo',
            'description' => '<script>alert("teste")</script>',
        ]);
        $project->load('clientSystems');

        $this->actingAs($administrator);
        View::share('errors', new ViewErrorBag());

        $html = view('client-systems.settings-card', compact('project'))->render();

        $this->assertStringContainsString('Novo Sistema cliente', $html);
        $this->assertStringContainsString('Sistema externo', $html);
        $this->assertStringContainsString('&lt;script&gt;alert(&quot;teste&quot;)&lt;/script&gt;', $html);
        $this->assertStringNotContainsString('<script>alert("teste")</script>', $html);
        $this->assertStringContainsString(
            route('projects.client-systems.api-keys', [$project, $clientSystem]),
            $html,
        );
        $this->assertStringNotContainsString('Excluir Sistema cliente', $html);
        $this->assertStringNotContainsString('Desativar Sistema cliente', $html);
    }

    public function test_package_validates_metadata_and_supports_optional_expiration_renewal_and_revocation(): void
    {
        $administrator = $this->user('Administradora');
        $project = $this->project('Projeto de ciclo');
        $project->users()->attach($administrator, ['role' => 'ADMIN']);
        $clientSystem = $project->clientSystems()->create(['name' => 'Robô institucional']);
        $storeRoute = route('api-keys.keys.store', ['client-system', $clientSystem]);

        $this->actingAs($administrator)
            ->from(route('projects.client-systems.api-keys', [$project, $clientSystem]))
            ->post($storeRoute, [
                'name' => 'Metadados inválidos',
                'purpose' => 'callback',
                'role' => 'administrator',
                'expires_at' => now()->subDay()->toDateString(),
            ])
            ->assertSessionHasErrors(['purpose', 'role', 'expires_at']);

        $this->actingAs($administrator)
            ->post($storeRoute, [
                'name' => 'Integração sem expiração',
                'purpose' => 'integration',
                'role' => 'contributor',
                'expires_at' => null,
            ])
            ->assertRedirect();

        $originalKey = ApiKey::query()->firstOrFail();
        $this->assertNull($originalKey->expires_at);

        $this->actingAs($administrator)
            ->post(route('api-keys.keys.renew', ['client-system', $clientSystem, $originalKey]), [
                'name' => 'Integração renovada',
                'purpose' => 'ai',
                'role' => 'viewer',
                'expires_at' => now()->addDays(3)->toDateString(),
            ])
            ->assertRedirect();

        $originalKey->refresh();
        $renewedKey = ApiKey::query()->whereKeyNot($originalKey->id)->firstOrFail();

        $this->assertTrue($originalKey->isRevoked());
        $this->assertSame($administrator->id, $originalKey->revoked_by);
        $this->assertSame('client-system', $renewedKey->owner_type);
        $this->assertSame($clientSystem->id, $renewedKey->owner_id);
        $this->assertSame('ai', $renewedKey->purpose);
        $this->assertSame('viewer', $renewedKey->role);

        $this->actingAs($administrator)
            ->post(route('api-keys.keys.revoke', ['client-system', $clientSystem, $renewedKey]))
            ->assertRedirect();

        $this->assertTrue($renewedKey->refresh()->isRevoked());
        $this->assertSame($administrator->id, $renewedKey->revoked_by);
    }

    public function test_project_deletion_revokes_active_keys_and_restoration_does_not_reactivate_them(): void
    {
        $administrator = $this->user('Administradora');
        $project = $this->project('Projeto removido');
        $project->users()->attach($administrator, ['role' => 'ADMIN']);
        $clientSystem = $project->clientSystems()->create(['name' => 'Integração histórica']);
        $created = app(ApiKeyManager::class)->create(
            $clientSystem,
            'Credencial ativa',
            'integration',
            'viewer',
            null,
            $administrator->id,
        );

        $this->actingAs($administrator)
            ->delete(route('projects.destroy', $project))
            ->assertRedirect();

        $this->assertSoftDeleted('projects', ['id' => $project->id]);
        $this->assertDatabaseHas('client_systems', [
            'id' => $clientSystem->id,
            'project_id' => $project->id,
        ]);
        $this->assertTrue($created->apiKey->refresh()->isRevoked());
        $this->assertSame($administrator->id, $created->apiKey->revoked_by);

        $this->actingAs($administrator)
            ->post(route('api-keys.keys.store', ['client-system', $clientSystem]), [
                'name' => 'Credencial após exclusão',
                'purpose' => 'integration',
                'role' => 'viewer',
            ])
            ->assertForbidden();

        $project->refresh()->restore();

        $this->assertNull(app(ApiKeyManager::class)->authenticate($created->plainTextToken()));
    }

    public function test_client_system_from_another_project_is_hidden_by_nested_routes(): void
    {
        $globalAdministrator = $this->globalAdministrator('Administradora global');
        $firstProject = $this->project('Projeto um');
        $secondProject = $this->project('Projeto dois');
        $clientSystem = $firstProject->clientSystems()->create(['name' => 'Sistema restrito']);

        $this->actingAs($globalAdministrator)
            ->get(route('projects.client-systems.api-keys', [$secondProject, $clientSystem]))
            ->assertNotFound();

        $this->actingAs($globalAdministrator)
            ->patch(route('projects.client-systems.update', [$secondProject, $clientSystem]), [
                'name' => 'Tentativa cruzada',
            ])
            ->assertNotFound();

        $this->assertSame('Sistema restrito', $clientSystem->refresh()->name);
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
        $permission = Permission::findByName('admin', 'senhaunica');
        $user->givePermissionTo($permission);

        return $user;
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
            $table->string('phase')->nullable();
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
        Schema::create('tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id');
            $table->boolean('deleted_via_project')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('tags', function (Blueprint $table): void {
            $table->id();
            $table->json('name');
            $table->json('slug');
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
        (require database_path('migrations/2026_09_14_000000_create_client_systems_table.php'))->up();

        DB::table('permissions')->insert(collect([
            'admin', 'boss', 'manager', 'poweruser', 'user',
        ])->map(fn (string $name) => [
            'name' => $name,
            'guard_name' => 'senhaunica',
            'created_at' => now(),
            'updated_at' => now(),
        ])->all());
    }
}
