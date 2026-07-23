<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Meeting;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class FileExposureTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'http://localhost',
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);
        URL::forceRootUrl('http://localhost');

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        $this->createSchema();
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');

        parent::tearDown();
    }

    public function test_viewer_can_read_metadata_by_uuid_without_receiving_the_original_name(): void
    {
        Storage::fake('files');
        Queue::fake();

        $author = $this->user('Autor');
        $viewer = $this->user('Visualizador');
        $project = $this->projectWithMembers($author, $viewer);

        $this->actingAs($author);
        $media = $project
            ->addMedia(UploadedFile::fake()->createWithContent('proveniencia-secreta.pdf', 'conteudo'))
            ->toMediaCollection();

        $this->actingAs($viewer)
            ->get(route('files.metadata', ['uuid' => $media->uuid]))
            ->assertOk()
            ->assertJsonPath('uuid', $media->uuid)
            ->assertJsonPath('name', 'proveniencia-secreta')
            ->assertJsonMissing(['original_name' => 'proveniencia-secreta.pdf']);
    }

    public function test_metadata_hides_inaccessible_missing_and_soft_deleted_owners_as_not_found(): void
    {
        Storage::fake('files');
        Queue::fake();

        $author = $this->user('Autor');
        $viewer = $this->user('Visualizador');
        $outsider = $this->user('Sem acesso');
        $project = $this->projectWithMembers($author, $viewer);

        $this->actingAs($author);
        $media = $project
            ->addMedia(UploadedFile::fake()->createWithContent('restrito.txt', 'conteudo'))
            ->toMediaCollection();

        $this->actingAs($outsider)
            ->get(route('files.metadata', ['uuid' => $media->uuid]))
            ->assertNotFound();

        $this->actingAs($viewer)
            ->get(route('files.metadata', ['uuid' => '00000000-0000-4000-8000-000000000000']))
            ->assertNotFound();

        $project->delete();

        $this->actingAs($viewer)
            ->get(route('files.metadata', ['uuid' => $media->uuid]))
            ->assertNotFound();
    }

    public function test_authorized_download_is_an_attachment_with_a_safe_display_name_and_no_audit_event(): void
    {
        Storage::fake('files');
        Queue::fake();

        $author = $this->user('Autor');
        $viewer = $this->user('Visualizador');
        $project = $this->projectWithMembers($author, $viewer);

        $this->actingAs($author);
        $media = $project
            ->addMedia(UploadedFile::fake()->createWithContent('origem.html', '<script>conteudo ativo</script>'))
            ->toMediaCollection();
        $media->display_name = "Relatório \"final\"\n";
        $media->save();

        $this->actingAs($viewer)
            ->get(route('files.download', ['uuid' => $media->uuid]))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Content-Disposition', 'attachment; filename="Relatorio final.html"');

        $this->assertDatabaseMissing('activity_log', [
            'subject_id' => $media->id,
            'event' => 'downloaded',
        ]);
    }

    public function test_project_contributor_uploads_one_file_with_audit_and_size_validation(): void
    {
        Storage::fake('files');
        Queue::fake();

        $admin = $this->user('Admin');
        $contributor = $this->user('Colaborador');
        $project = $this->projectWithMembers($admin, $contributor);
        $project->users()->updateExistingPivot($contributor->id, ['role' => 'CONTRIBUTOR']);

        $this->actingAs($contributor)
            ->post(route('projects.files.store', $project), [
                'file' => UploadedFile::fake()->create('evidencia.pdf', 40),
            ])
            ->assertRedirect();

        $media = $project->fresh()->getFirstMedia();

        $this->assertNotNull($media);
        $this->assertSame($contributor->id, $media->uploaded_by);
        Storage::disk('files')->assertExists($media->getPathRelativeToRoot());
        Queue::assertPushed(\App\Jobs\GenerateFileThumbnail::class);
        $this->assertDatabaseHas('activity_log', [
            'subject_id' => $media->id,
            'event' => 'uploaded',
        ]);

        $this->actingAs($contributor)
            ->from(route('projects.files.store', $project))
            ->post(route('projects.files.store', $project), [
                'file' => UploadedFile::fake()->create('grande.bin', 102401),
            ])
            ->assertRedirect(route('projects.files.store', $project))
            ->assertSessionHasErrors('file');
    }

    public function test_author_can_rename_and_definitively_delete_without_changing_file_identity(): void
    {
        Storage::fake('files');
        Queue::fake();

        $author = $this->user('Autor');
        $viewer = $this->user('Visualizador');
        $project = $this->projectWithMembers($author, $viewer);

        $this->actingAs($author);
        $media = $project
            ->addMedia(UploadedFile::fake()->createWithContent('origem.txt', 'conteudo'))
            ->toMediaCollection();
        $originalName = $media->original_name;
        $uuid = $media->uuid;
        $physicalName = $media->file_name;
        $path = $media->getPathRelativeToRoot();

        $this->actingAs($viewer)
            ->patch(route('files.update', ['uuid' => $uuid]), ['name' => 'Não autorizado'])
            ->assertForbidden();

        $this->actingAs($author)
            ->patch(route('files.update', ['uuid' => $uuid]), ['name' => 'Nome revisado'])
            ->assertRedirect();

        $media->refresh();
        $this->assertSame('Nome revisado', $media->display_name);
        $this->assertSame($uuid, $media->uuid);
        $this->assertSame($physicalName, $media->file_name);
        $this->assertSame($originalName, $media->original_name);
        $this->assertDatabaseHas('activity_log', [
            'subject_id' => $media->id,
            'event' => 'renamed',
        ]);

        $this->actingAs($author)
            ->delete(route('files.destroy', ['uuid' => $uuid]))
            ->assertRedirect();

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        Storage::disk('files')->assertMissing($path);
        $this->assertDatabaseHas('activity_log', [
            'subject_id' => $media->id,
            'event' => 'deleted',
        ]);
    }

    public function test_task_done_blocks_mutations_while_completed_meeting_keeps_upload_open(): void
    {
        Storage::fake('files');
        Queue::fake();

        $admin = $this->user('Admin');
        $contributor = $this->user('Colaborador');
        $project = $this->projectWithMembers($admin, $contributor);
        $project->users()->updateExistingPivot($contributor->id, ['role' => 'CONTRIBUTOR']);

        $task = Task::query()->create([
            'project_id' => $project->id,
            'title' => 'Tarefa concluída',
            'status' => 'DONE',
        ]);
        $meeting = Meeting::query()->create([
            'title' => 'Reunião concluída',
            'status' => 'COMPLETED',
        ]);
        $meeting->projects()->attach($project);

        $this->actingAs($contributor)
            ->post(route('tasks.files.store', $task), [
                'file' => UploadedFile::fake()->create('bloqueado.txt', 1),
            ])
            ->assertForbidden();

        $this->actingAs($contributor)
            ->post(route('meetings.files.store', $meeting), [
                'file' => UploadedFile::fake()->create('registro.txt', 1),
            ])
            ->assertRedirect();

        $this->assertNotNull($meeting->fresh()->getFirstMedia());
    }

    public function test_thumbnail_is_private_and_available_only_after_processing(): void
    {
        Storage::fake('files');
        Queue::fake();

        $author = $this->user('Autor');
        $viewer = $this->user('Visualizador');
        $project = $this->projectWithMembers($author, $viewer);

        $this->actingAs($author);
        $media = $project
            ->addMedia(UploadedFile::fake()->image('foto.png', 20, 20))
            ->toMediaCollection();
        $thumbnailPath = $media->id.'/conversions/'.pathinfo($media->file_name, PATHINFO_FILENAME).'-thumbnail.jpg';
        Storage::disk('files')->put($thumbnailPath, 'thumbnail-content');
        $media->setCustomProperty('thumbnail_status', 'ready');
        $media->save();

        $this->actingAs($viewer)
            ->get(route('files.thumbnail', ['uuid' => $media->uuid]))
            ->assertOk()
            ->assertHeader('Content-Type', 'image/jpeg');

        $media->setCustomProperty('thumbnail_status', 'pending');
        $media->save();

        $this->actingAs($viewer)
            ->get(route('files.thumbnail', ['uuid' => $media->uuid]))
            ->assertNotFound();
    }

    public function test_global_administrator_can_manage_an_accessible_owner_file_without_membership(): void
    {
        Storage::fake('files');
        Queue::fake();

        $author = $this->user('Autor');
        $viewer = $this->user('Visualizador');
        $administrator = $this->user('Administradora global');
        $project = $this->projectWithMembers($author, $viewer);

        DB::table('model_has_permissions')->insert([
            'permission_id' => DB::table('permissions')->where('name', 'admin')->value('id'),
            'model_type' => User::class,
            'model_id' => $administrator->id,
        ]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($author);
        $media = $project
            ->addMedia(UploadedFile::fake()->createWithContent('original-restrito.txt', 'conteudo'))
            ->toMediaCollection();

        $this->actingAs($administrator)
            ->get(route('files.metadata', ['uuid' => $media->uuid]))
            ->assertOk()
            ->assertJsonPath('original_name', 'original-restrito.txt');

        $this->actingAs($administrator)
            ->patch(route('files.update', ['uuid' => $media->uuid]), ['name' => 'Moderado'])
            ->assertRedirect();

        $this->assertSame('Moderado', $media->fresh()->display_name);
    }

    private function createSchema(): void
    {
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
            $table->string('title');
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
            $table->string('status');
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->foreignId('deleted_by')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('meeting_projects', function (Blueprint $table): void {
            $table->foreignId('meeting_id');
            $table->foreignId('project_id');
        });
        Schema::create('tags', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug');
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
        });
        Schema::create('model_has_roles', function (Blueprint $table): void {
            $table->foreignId('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });
        Schema::create('role_has_permissions', function (Blueprint $table): void {
            $table->foreignId('permission_id');
            $table->foreignId('role_id');
        });

        $migration = require database_path('migrations/2026_07_21_090000_create_media_table.php');
        $migration->up();
        (require database_path('migrations/2026_07_22_090000_create_meeting_file_shares_table.php'))->up();

        DB::table('permissions')->insert(collect([
            'admin', 'boss', 'manager', 'poweruser', 'user',
        ])->map(fn (string $name) => [
            'name' => $name,
            'guard_name' => 'senhaunica',
        ])->all());
    }

    private function user(string $name): User
    {
        return User::query()->create(['name' => $name]);
    }

    private function projectWithMembers(User $author, User $viewer): Project
    {
        $project = new Project();
        $project->forceFill([
            'name' => 'Projeto de Arquivos',
            'slug' => 'projeto-arquivos',
            'status' => 'ACTIVE',
        ])->save();

        $project->users()->attach($author, ['role' => 'ADMIN']);
        $project->users()->attach($viewer, ['role' => 'VIEWER']);

        return $project;
    }
}
