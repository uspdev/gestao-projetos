<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Link;
use App\Models\Meeting;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\ViewErrorBag;
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
        Queue::assertNothingPushed();
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

    public function test_multiple_file_upload_keeps_valid_files_when_another_file_is_invalid(): void
    {
        Storage::fake('files');
        Queue::fake();

        $admin = $this->user('Admin de envio múltiplo');
        $contributor = $this->user('Colaborador de envio múltiplo');
        $project = $this->projectWithMembers($admin, $contributor);
        $project->users()->updateExistingPivot($contributor->id, ['role' => 'CONTRIBUTOR']);

        $this->actingAs($contributor)
            ->post(route('projects.files.store', $project), [
                'files' => [
                    UploadedFile::fake()->create('valido.txt', 1),
                    UploadedFile::fake()->create('grande.bin', 102401),
                ],
            ])
            ->assertRedirect()
            ->assertSessionHasErrors('files')
            ->assertSessionHas('alert-success');

        $this->assertSame('valido', $project->fresh()->getFirstMedia()?->display_name);
    }

    public function test_project_contributor_can_add_distinct_external_links_in_batch(): void
    {
        $admin = $this->user('Admin de Links');
        $contributor = $this->user('Colaborador de Links');
        $project = $this->projectWithMembers($admin, $contributor);
        $project->users()->updateExistingPivot($contributor->id, ['role' => 'CONTRIBUTOR']);

        $this->actingAs($contributor)
            ->post(route('projects.links.store', $project), [
                'urls' => "https://example.test/manual\n\nhttps://example.test/planilha\nhttps://example.test/manual",
            ])
            ->assertRedirect()
            ->assertSessionHas('alert-success');

        $links = $project->fresh()->links()->orderBy('url')->get();
        $this->assertCount(2, $links);
        $this->assertSame('https://example.test/manual', $links->first()->display_name);
        $this->assertTrue($links->every(fn (Link $link) => $link->created_by === $contributor->id));
        $this->assertDatabaseHas('activity_log', ['subject_id' => $links->first()->id, 'event' => 'created']);
    }

    public function test_link_batch_rejects_non_http_urls_without_creating_any_link(): void
    {
        $admin = $this->user('Admin de URLs');
        $contributor = $this->user('Colaborador de URLs');
        $project = $this->projectWithMembers($admin, $contributor);
        $project->users()->updateExistingPivot($contributor->id, ['role' => 'CONTRIBUTOR']);

        $this->actingAs($contributor)
            ->from(route('projects.show', $project))
            ->post(route('projects.links.store', $project), [
                'urls' => "https://example.test/valido\nftp://example.test/invalido",
            ])
            ->assertRedirect(route('projects.show', $project))
            ->assertSessionHasErrors('urls');

        $this->assertDatabaseCount('links', 0);
    }

    public function test_author_can_edit_and_delete_a_link(): void
    {
        $author = $this->user('Autor de Link');
        $viewer = $this->user('Leitor de Link');
        $project = $this->projectWithMembers($author, $viewer);
        $link = $project->links()->create([
            'name' => 'Manual',
            'url' => 'https://example.test/manual',
            'created_by' => $author->id,
        ]);

        $this->actingAs($author)
            ->patch(route('links.update', $link->uuid), [
                'name' => 'Manual atualizado',
                'url' => 'https://example.test/manual-v2',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('links', ['id' => $link->id, 'name' => 'Manual atualizado', 'url' => 'https://example.test/manual-v2']);

        $this->actingAs($author)
            ->delete(route('links.destroy', $link->uuid))
            ->assertRedirect();

        $this->assertDatabaseMissing('links', ['id' => $link->id]);
    }

    public function test_file_actions_render_an_edit_region_for_each_resource(): void
    {
        Storage::fake('files');
        Queue::fake();

        $author = $this->user('Autor das ações de Arquivos');
        $viewer = $this->user('Leitor das ações de Arquivos');
        $project = $this->projectWithMembers($author, $viewer);
        $project->links()->create([
            'name' => 'Manual',
            'url' => 'https://example.test/manual',
            'created_by' => $author->id,
        ]);

        $this->actingAs($author);
        $project
            ->addMedia(UploadedFile::fake()->createWithContent('manual.txt', 'conteudo'))
            ->toMediaCollection();

        $html = view('components.files.list', [
            'owner' => $project,
            'files' => $project->media()->with('uploader')->paginate(20, ['*'], 'files_page'),
            'links' => $project->links()->with('creator')->paginate(20, ['*'], 'links_page'),
            'errors' => new ViewErrorBag(),
        ])->render();

        $this->assertStringContainsString('data-file-edit-region', $html);
        $this->assertStringNotContainsString('data-file-rename-region', $html);
        $this->assertStringContainsString('data-link-edit-form', $html);
        $this->assertStringContainsString('Nome do link', $html);
        $this->assertStringContainsString('fas fa-save', $html);
        $this->assertStringContainsString('fas fa-times', $html);
        $this->assertStringNotContainsString('data-file-preview-toggle', $html);
        $this->assertStringNotContainsString('Pré-visualização indisponível', $html);
    }

    public function test_meeting_editor_can_share_and_revoke_an_eligible_link(): void
    {
        $author = $this->user('Autor que compartilha Link');
        $viewer = $this->user('Leitor da reunião');
        $project = $this->projectWithMembers($author, $viewer);
        $link = $project->links()->create([
            'name' => 'Documento de pauta',
            'url' => 'https://example.test/pauta',
            'created_by' => $author->id,
        ]);
        $meeting = Meeting::query()->create(['title' => 'Reunião de Links', 'status' => 'SCHEDULED']);
        $meeting->projects()->attach($project);
        DB::table('model_has_permissions')->insert([
            'permission_id' => DB::table('permissions')->where('name', 'admin')->value('id'),
            'model_type' => User::class,
            'model_id' => $author->id,
        ]);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->actingAs($author)
            ->post(route('meetings.link-shares.store', $meeting), ['link_uuid' => $link->uuid])
            ->assertRedirect();

        $this->assertDatabaseHas('meeting_link_shares', ['meeting_id' => $meeting->id, 'link_id' => $link->id]);

        $this->actingAs($author)
            ->delete(route('meetings.link-shares.destroy', [$meeting, $link->uuid]))
            ->assertRedirect();

        $this->assertDatabaseMissing('meeting_link_shares', ['meeting_id' => $meeting->id, 'link_id' => $link->id]);
    }

    public function test_thumbnail_failure_rejects_the_upload_and_reports_the_error(): void
    {
        Storage::fake('files');
        Queue::fake();
        config(['media-library.thumbnail_max_side' => 0]);

        $author = $this->user('Autor');
        $viewer = $this->user('Visualizador');
        $project = $this->projectWithMembers($author, $viewer);

        $this->actingAs($author)
            ->from(route('projects.files.store', $project))
            ->post(route('projects.files.store', $project), [
                'file' => UploadedFile::fake()->image('foto.png', 20, 20),
            ])
            ->assertRedirect(route('projects.files.store', $project))
            ->assertSessionHasErrors([
                'file' => 'Não foi possível processar a miniatura. O Arquivo não foi enviado. Tente novamente.',
            ]);

        Queue::assertNothingPushed();
        $this->assertDatabaseCount('media', 0);
        $this->assertDatabaseMissing('activity_log', ['event' => 'uploaded']);
        $this->assertSame([], Storage::disk('files')->allFiles());
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

    public function test_thumbnail_is_private_and_available_only_when_ready(): void
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

        $media->setCustomProperty('thumbnail_status', 'not_supported');
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
        (require database_path('migrations/2026_08_17_090000_create_links_table.php'))->up();
        (require database_path('migrations/2026_08_17_090100_create_meeting_link_shares_table.php'))->up();

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
