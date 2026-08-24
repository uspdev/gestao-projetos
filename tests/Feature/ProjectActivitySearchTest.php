<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\Task;
use App\Services\ProjectActivityService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProjectActivitySearchTest extends TestCase
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
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');

        parent::tearDown();
    }

    public function test_it_finds_a_task_when_searching_by_the_label_shown_in_the_activity_list(): void
    {
        DB::table('projects')->insert([
            'name' => 'Projeto de testes',
            'slug' => 'projeto-de-testes',
            'status' => 'ACTIVE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $project = Project::query()->firstOrFail();

        DB::table('tasks')->insert([
            'project_id' => $project->id,
            'title' => 'Bug ao criar comentário',
            'status' => 'NEW',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $task = Task::query()->firstOrFail();

        DB::table('activity_log')->insert([
            'log_name' => 'task',
            'description' => 'updated',
            'event' => 'updated',
            'subject_type' => 'task',
            'subject_id' => $task->id,
            'properties' => json_encode(['attributes' => ['title' => $task->title]]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $activities = app(ProjectActivityService::class)->paginate($project, [
            'search' => 'Tarefa: Bug ao criar comentário',
        ]);

        $this->assertSame(1, $activities->total());
        $this->assertSame('Tarefa: Bug ao criar comentário', $activities->first()['subject']);

        $activities = app(ProjectActivityService::class)->paginate($project, [
            'search' => 'Bug ao criar comentário',
        ]);

        $this->assertSame(1, $activities->total());
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('codpes')->nullable();
            $table->timestamps();
        });

        Schema::create('projects', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug');
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
            $table->text('description')->nullable();
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
            $table->string('location')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('meeting_projects', function (Blueprint $table): void {
            $table->foreignId('meeting_id');
            $table->foreignId('project_id');
        });

        Schema::create('meeting_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('meeting_id');
            $table->string('discussable_type')->nullable();
            $table->unsignedBigInteger('discussable_id')->nullable();
            $table->string('title')->nullable();
            $table->text('notes')->nullable();
        });

        Schema::create('comments', function (Blueprint $table): void {
            $table->id();
            $table->string('commentable_type');
            $table->unsignedBigInteger('commentable_id');
            $table->text('text');
        });

        Schema::create('media', function (Blueprint $table): void {
            $table->id();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->string('name')->nullable();
            $table->string('original_name')->nullable();
            $table->string('uuid')->nullable();
        });

        Schema::create('links', function (Blueprint $table): void {
            $table->id();
            $table->string('linkable_type');
            $table->unsignedBigInteger('linkable_id');
            $table->string('name')->nullable();
            $table->text('url')->nullable();
        });

        Schema::create('activity_log', function (Blueprint $table): void {
            $table->id();
            $table->string('log_name')->nullable();
            $table->text('description');
            $table->string('subject_type')->nullable();
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('causer_type')->nullable();
            $table->unsignedBigInteger('causer_id')->nullable();
            $table->json('properties')->nullable();
            $table->string('event')->nullable();
            $table->uuid('batch_uuid')->nullable();
            $table->timestamps();
        });
    }
}
