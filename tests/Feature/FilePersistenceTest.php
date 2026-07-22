<?php

namespace Tests\Feature;

use App\Jobs\GenerateFileThumbnail;
use App\Models\Meeting;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use LogicException;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileNameNotAllowed;
use Spatie\MediaLibrary\MediaCollections\Exceptions\FileIsTooBig;
use Tests\TestCase;

class FilePersistenceTest extends TestCase
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

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
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
        Schema::create('tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id');
            $table->string('title');
            $table->string('status');
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('meetings', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('status');
            $table->timestamps();
            $table->softDeletes();
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

        $migration = require database_path('migrations/2026_07_21_090000_create_media_table.php');
        $migration->up();
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');

        parent::tearDown();
    }

    public function test_only_project_task_and_meeting_are_file_owners(): void
    {
        foreach ([Project::class, Task::class, Meeting::class] as $owner) {
            $this->assertTrue(
                method_exists($owner, 'addMedia'),
                "{$owner} must expose the Media Library file-owner interface."
            );
        }
    }

    public function test_file_upload_uses_the_private_disk_and_preserves_its_provenance(): void
    {
        Storage::fake('files');
        Queue::fake();

        $project = new Project();
        $project->forceFill([
            'name' => 'Projeto de Arquivos',
            'slug' => 'projeto-arquivos',
            'status' => 'ACTIVE',
        ])->save();

        $upload = UploadedFile::fake()->createWithContent('relatório final.PNG', 'conteúdo');
        $media = $project->addMedia($upload)->toMediaCollection();

        $this->assertSame('files', $media->disk);
        $this->assertSame('relatório final.PNG', $media->original_name);
        $this->assertSame('relatório final', $media->display_name);
        $this->assertTrue(Str::isUuid(pathinfo($media->uuid_name, PATHINFO_FILENAME)));
        $this->assertSame('png', pathinfo($media->uuid_name, PATHINFO_EXTENSION));
        $this->assertSame($media->uuid, pathinfo($media->uuid_name, PATHINFO_FILENAME));
        $this->assertSame('pending', $media->getCustomProperty('thumbnail_status'));
        Storage::disk('files')->assertExists($media->getPathRelativeToRoot());
        Queue::assertPushed(GenerateFileThumbnail::class);
    }

    public function test_executable_server_script_extensions_remain_blocked_even_when_disguised(): void
    {
        Storage::fake('files');

        $project = $this->project();
        $upload = UploadedFile::fake()->createWithContent('relatorio.php.jpg', 'conteúdo');

        $this->expectException(FileNameNotAllowed::class);

        $project->addMedia($upload)->toMediaCollection();
    }

    public function test_thumbnail_job_generates_a_private_thumbnail_for_a_valid_raster_image(): void
    {
        Storage::fake('files');
        Queue::fake();

        $media = $this->project()
            ->addMedia(UploadedFile::fake()->image('foto.png', 120, 80))
            ->toMediaCollection();

        (new GenerateFileThumbnail($media))->handle();

        $media->refresh();

        $this->assertSame('ready', $media->getCustomProperty('thumbnail_status'));
        Storage::disk('files')->assertExists(
            $media->id.'/conversions/'.pathinfo($media->file_name, PATHINFO_FILENAME).'-thumbnail.jpg'
        );
    }

    public function test_unsupported_thumbnail_does_not_affect_the_original_download(): void
    {
        Storage::fake('files');
        Queue::fake();

        $media = $this->project()
            ->addMedia(UploadedFile::fake()->createWithContent('manual.pdf', '%PDF-1.7'))
            ->toMediaCollection();

        (new GenerateFileThumbnail($media))->handle();

        $media->refresh();

        $this->assertSame('not_supported', $media->getCustomProperty('thumbnail_status'));
        Storage::disk('files')->assertExists($media->getPathRelativeToRoot());
    }

    public function test_soft_deleted_owner_preserves_files_and_force_deletion_removes_them(): void
    {
        Storage::fake('files');
        Queue::fake();

        $project = $this->project();
        $media = $project
            ->addMedia(UploadedFile::fake()->createWithContent('registro.txt', 'conteúdo'))
            ->toMediaCollection();

        $conversionPath = $media->id.'/conversions/'
            .pathinfo($media->file_name, PATHINFO_FILENAME).'-thumbnail.jpg';
        Storage::disk('files')->put($conversionPath, 'miniatura');

        $project->delete();

        $this->assertDatabaseHas('media', ['id' => $media->id]);
        Storage::disk('files')->assertExists($media->getPathRelativeToRoot());
        Storage::disk('files')->assertExists($conversionPath);

        $project->restore();

        $this->assertSame($media->id, $project->fresh()->getFirstMedia()->id);

        $project->forceDelete();

        $this->assertDatabaseMissing('media', ['id' => $media->id]);
        Storage::disk('files')->assertMissing($media->getPathRelativeToRoot());
        Storage::disk('files')->assertMissing($conversionPath);
    }

    public function test_only_the_display_name_can_be_renamed_after_upload(): void
    {
        Storage::fake('files');
        Queue::fake();

        $media = $this->project()
            ->addMedia(UploadedFile::fake()->createWithContent('evidencia.txt', 'conteúdo'))
            ->toMediaCollection();

        $media->display_name = 'Evidência revisada';
        $media->save();

        $this->assertSame('Evidência revisada', $media->fresh()->display_name);

        $this->expectException(LogicException::class);

        $media->uuid_name = 'outro-nome.txt';
    }

    public function test_file_author_is_immutable_after_upload(): void
    {
        Storage::fake('files');
        Queue::fake();

        $media = $this->project()
            ->addMedia(UploadedFile::fake()->createWithContent('evidencia.txt', 'conteúdo'))
            ->toMediaCollection();

        $media->uploaded_by = 999;

        $this->expectException(LogicException::class);

        $media->save();
    }

    public function test_general_download_formats_are_accepted_without_an_extension_allowlist(): void
    {
        Storage::fake('files');
        Queue::fake();

        $project = $this->project();

        foreach (['ferramenta.exe', 'rotina.bat', 'script.sh'] as $fileName) {
            $media = $project
                ->addMedia(UploadedFile::fake()->createWithContent($fileName, 'conteúdo'))
                ->toMediaCollection();

            $this->assertSame(pathinfo($fileName, PATHINFO_EXTENSION), pathinfo($media->file_name, PATHINFO_EXTENSION));
            Storage::disk('files')->assertExists($media->getPathRelativeToRoot());
        }
    }

    public function test_file_larger_than_100_megabytes_is_rejected(): void
    {
        Storage::fake('files');

        $path = tempnam(sys_get_temp_dir(), 'arquivo-grande-');
        $handle = fopen($path, 'w');
        ftruncate($handle, 100 * 1024 * 1024 + 1);
        fclose($handle);

        try {
            $this->expectException(FileIsTooBig::class);

            $this->project()->addMedia($path)->toMediaCollection();
        } finally {
            @unlink($path);
        }
    }

    private function project(): Project
    {
        $project = new Project();
        $project->forceFill([
            'name' => 'Projeto de Arquivos',
            'slug' => 'projeto-arquivos',
            'status' => 'ACTIVE',
        ])->save();

        return $project;
    }
}
