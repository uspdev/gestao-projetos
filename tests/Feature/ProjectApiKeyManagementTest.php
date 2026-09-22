<?php

namespace Tests\Feature;

use App\Morphs\ApiKeyOwnerMap;
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

class ProjectApiKeyManagementTest extends TestCase
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

    public function test_only_project_owner_is_available_in_the_web_interface(): void
    {
        $administrator = $this->user('Administradora direta');
        $project = $this->project('Projeto integrado');
        $project->users()->attach($administrator, ['role' => 'ADMIN']);

        $this->assertSame(['project' => Project::class], config('api-keys.owners'));
        $this->assertSame(['project' => Project::class], ApiKeyOwnerMap::morphMap());

        $this->actingAs($administrator)
            ->get(route('projects.settings', $project))
            ->assertOk()
            ->assertSee('data-api-keys-manager', false)
            ->assertSee('value="project"', false)
            ->assertDontSee('Sistema cliente');

        $this->post('/projects/'.$project->slug.'/client-systems', [
            'name' => 'Sistema recusado',
        ])->assertMethodNotAllowed();

        $this->get('/projects/'.$project->slug.'/client-systems/1/api-keys')
            ->assertNotFound();

        $this->post('/api-keys/client-system/1/keys', [
            'name' => 'Chave recusada',
            'purpose' => 'integration',
            'role' => 'viewer',
        ])->assertNotFound();
    }

    public function test_project_manager_is_in_settings_and_only_direct_administrators_can_use_package_routes(): void
    {
        $parent = $this->project('Projeto pai');
        $project = $this->project('Projeto das chaves', $parent);
        $administrator = $this->user('Administradora direta');
        $contributor = $this->user('Contribuidor direto');
        $viewer = $this->user('Visualizador direto');
        $inherited = $this->user('Administrador herdado');
        $global = $this->globalAdministrator('Administrador global');
        $project->users()->attach($administrator, ['role' => 'ADMIN']);
        $project->users()->attach($contributor, ['role' => 'CONTRIBUTOR']);
        $project->users()->attach($viewer, ['role' => 'VIEWER']);
        $parent->users()->attach($inherited, ['role' => 'ADMIN']);

        $store = route('api-keys.keys.store', ['project', $project]);
        $this->actingAs($administrator)
            ->get(route('projects.settings', $project))
            ->assertOk()
            ->assertSee('data-api-keys-manager', false)
            ->assertSee('value="project"', false)
            ->assertSee('value="integration"', false)
            ->assertSee('value="ai"', false)
            ->assertSee('value="viewer"', false)
            ->assertSee('value="contributor"', false);

        foreach ([$contributor, $viewer, $inherited, $global] as $user) {
            $this->actingAs($user)
                ->get(route('projects.settings', $project))
                ->assertOk()
                ->assertDontSee('data-api-keys-manager', false);

            $this->actingAs($user)->post($store, [
                'name' => 'Chave recusada',
                'purpose' => 'integration',
                'role' => 'viewer',
            ])->assertForbidden();
        }

        $this->actingAs($administrator)->post($store, [
            'name' => 'Chave direta',
            'purpose' => 'ai',
            'role' => 'viewer',
        ])->assertRedirect();

        $key = ApiKey::query()->where('owner_type', 'project')->firstOrFail();
        $this->assertSame($project->id, $key->owner_id);
        $this->assertSame('project', $key->owner_type);
        $this->assertSame('ai', $key->purpose);
        $this->assertSame('viewer', $key->role);

        $firstDisplay = $this->get(route('projects.settings', $project))
            ->assertOk()
            ->assertSee('Ela será exibida apenas uma vez.');
        $this->assertSame(1, preg_match('/gpp_[A-Z0-9]{6}\.[A-Za-z0-9_-]+/', $firstDisplay->getContent(), $matches));
        $this->get(route('projects.settings', $project))
            ->assertOk()
            ->assertDontSee($matches[0]);

        foreach ([$contributor, $viewer, $inherited, $global] as $user) {
            $this->actingAs($user)
                ->post(route('api-keys.keys.renew', ['project', $project, $key]), [
                    'name' => 'Renovação recusada',
                    'purpose' => 'ai',
                    'role' => 'viewer',
                ])->assertForbidden();
            $this->actingAs($user)
                ->post(route('api-keys.keys.revoke', ['project', $project, $key]))
                ->assertForbidden();
        }
    }

    public function test_project_keys_have_read_abilities_and_package_renewal_and_revocation(): void
    {
        $project = $this->project('Projeto de leitura');
        $administrator = $this->user('Administradora de leitura');
        $project->users()->attach($administrator, ['role' => 'ADMIN']);
        $this->actingAs($administrator);

        foreach (['viewer', 'contributor'] as $role) {
            $this->post(route('api-keys.keys.store', ['project', $project]), [
                'name' => 'Chave '.$role,
                'purpose' => $role === 'viewer' ? 'integration' : 'ai',
                'role' => $role,
            ])->assertRedirect();
        }

        $keys = $project->apiKeys()->orderBy('id')->get();
        $this->assertCount(2, $keys);
        foreach ($keys as $key) {
            foreach (['projects.read', 'meetings.read', 'tasks.read', 'files.read'] as $ability) {
                $this->assertTrue($key->allows($ability));
            }
            $this->assertFalse($key->allows('*'));
            $this->assertFalse($key->allows('requests.read'));
            $this->assertFalse($key->allows('requests.create'));
        }

        $this->post(route('api-keys.keys.renew', ['project', $project, $keys[0]]), [
            'name' => 'Chave renovada',
            'purpose' => 'ai',
            'role' => 'contributor',
        ])->assertRedirect();
        $this->assertTrue($keys[0]->refresh()->isRevoked());
        $replacement = $project->apiKeys()->whereKeyNot($keys[0]->id)->latest('id')->firstOrFail();
        $this->assertSame('contributor', $replacement->role);
        $this->post(route('api-keys.keys.revoke', ['project', $project, $replacement]))
            ->assertRedirect();
        $this->assertTrue($replacement->refresh()->isRevoked());
        app(ApiKeyManager::class)->revoke($keys[1]);
        $this->get(route('projects.settings', $project))
            ->assertOk()
            ->assertSee('data-api-url-warning class="text-muted', false);
    }

    public function test_project_deletion_revokes_its_direct_keys_and_slug_warning_tracks_active_keys(): void
    {
        $project = $this->project('Projeto mutável');
        $administrator = $this->user('Administradora mutável');
        $project->users()->attach($administrator, ['role' => 'ADMIN']);
        $this->actingAs($administrator);

        $this->get(route('projects.settings', $project))
            ->assertOk()
            ->assertSee('data-api-url-warning class="text-muted', false);

        $created = app(ApiKeyManager::class)->create($project, 'Chave ativa', 'integration', 'viewer');
        $this->get(route('projects.settings', $project))
            ->assertOk()
            ->assertSee('data-api-url-warning class="alert alert-warning', false)
            ->assertSee('URLs da API');

        $oldSlug = $project->slug;
        $this->patch(route('projects.updateSlug', $project), ['slug' => 'projeto-renomeado'])
            ->assertRedirect(route('projects.settings', 'projeto-renomeado').'#'.deep_link_fragment($project));
        $this->getJson('/api/projects/'.$oldSlug)
            ->assertNotFound();
        $this->withToken($created->plainTextToken())
            ->getJson('/api/projects/projeto-renomeado')
            ->assertOk();

        $project->refresh();
        $this->delete(route('projects.destroy', $project))->assertRedirect();
        $this->assertTrue($created->apiKey->refresh()->isRevoked());
        $project->refresh()->restore();
        $this->assertDatabaseHas('projects', [
            'id' => $project->id,
            'deleted_at' => null,
        ]);
        $this->withToken($created->plainTextToken())
            ->getJson('/api/projects/'.$project->fresh()->slug)
            ->assertUnauthorized();
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
        Schema::create('project_types', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug');
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });
        Schema::create('phases', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug');
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
        Schema::create('modules', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug');
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

        DB::table('permissions')->insert(collect(array_unique(array_merge(
            User::$permissoesHierarquia,
            User::$permissoesVinculo,
            ['admin', 'boss', 'manager', 'poweruser', 'user'],
        )))->map(fn (string $name) => [
            'name' => $name,
            'guard_name' => 'senhaunica',
            'created_at' => now(),
            'updated_at' => now(),
        ])->all());
    }
}
