<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use App\Services\MentionExtractor;
use App\Services\MentionIndexer;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MentionIndexerTest extends TestCase
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
            $table->text('description')->nullable();
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
            $table->boolean('deleted_via_project')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('meetings', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->longText('notes')->nullable();
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

        (require database_path('migrations/2026_07_23_090000_create_mentions_table.php'))->up();
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');

        parent::tearDown();
    }

    public function test_it_preserves_historical_mentions_and_keeps_the_original_author_when_synchronizing(): void
    {
        $creator = User::query()->create(['name' => 'Pessoa autora']);
        $historicalUser = User::query()->create(['name' => 'Pessoa histórica']);
        $newAuthor = User::query()->create(['name' => 'Outra pessoa autora']);
        $project = $this->project($creator, $historicalUser);
        $indexer = new MentionIndexer(new MentionExtractor());
        $markdown = '@[Pessoa histórica](mention:user:' . $historicalUser->id . ')';

        $indexer->synchronize($project, 'description', $markdown, $creator->id);
        $mention = $project->mentions()->firstOrFail();
        $project->update(['description' => $markdown]);
        $project->users()->detach($historicalUser);

        $indexer->validateNewMentions($project, 'description', $markdown);
        $indexer->synchronize($project, 'description', $markdown, $newAuthor->id);

        $this->assertSame($mention->id, $project->mentions()->firstOrFail()->id);
        $this->assertSame($creator->id, $project->mentions()->firstOrFail()->created_by);

        $this->expectException(ValidationException::class);

        $indexer->validateNewMentions(
            $project,
            'description',
            $markdown . ' @[Outra pessoa](mention:user:' . $newAuthor->id . ')'
        );
    }

    public function test_it_removes_missing_mentions_and_creates_a_new_relation_when_they_return(): void
    {
        $firstAuthor = User::query()->create(['name' => 'Pessoa autora']);
        $secondAuthor = User::query()->create(['name' => 'Pessoa revisora']);
        $mentioned = User::query()->create(['name' => 'Pessoa mencionada']);
        $project = $this->project($firstAuthor, $mentioned);
        $indexer = new MentionIndexer(new MentionExtractor());
        $markdown = '@[Pessoa mencionada](mention:user:' . $mentioned->id . ')';

        $indexer->synchronize($project, 'description', $markdown, $firstAuthor->id);
        $firstMentionId = $project->mentions()->value('id');

        $indexer->synchronize($project, 'description', null, $secondAuthor->id);
        $this->assertDatabaseMissing('mentions', ['id' => $firstMentionId]);

        $indexer->synchronize($project, 'description', $markdown, $secondAuthor->id);

        $this->assertDatabaseHas('mentions', [
            'mentionable_id' => $project->id,
            'field' => 'description',
            'mentioned_user_id' => $mentioned->id,
            'created_by' => $secondAuthor->id,
        ]);
        $this->assertNotSame($firstMentionId, $project->mentions()->value('id'));
    }

    public function test_rebuild_command_is_idempotent_and_reports_its_counts(): void
    {
        $author = User::query()->create(['name' => 'Pessoa autora']);
        $mentioned = User::query()->create(['name' => 'Pessoa mencionada']);
        $project = $this->project($author, $mentioned);
        $project->update([
            'description' => '@[Pessoa mencionada](mention:user:' . $mentioned->id . ')',
        ]);

        $this->artisan('mentions:rebuild')
            ->expectsOutput('Reconstrução concluída: 1 fontes, 1 relações e 0 erros.')
            ->assertSuccessful();
        $mentionId = $project->mentions()->value('id');

        $this->artisan('mentions:rebuild')
            ->expectsOutput('Reconstrução concluída: 1 fontes, 1 relações e 0 erros.')
            ->assertSuccessful();

        $this->assertSame($mentionId, $project->mentions()->value('id'));
        $this->assertDatabaseCount('mentions', 1);
    }

    public function test_soft_deletion_clears_mentions_and_restoration_rebuilds_them_from_markdown(): void
    {
        $author = User::query()->create(['name' => 'Pessoa autora']);
        $mentioned = User::query()->create(['name' => 'Pessoa mencionada']);
        $project = $this->project($author, $mentioned);
        $markdown = '@[Pessoa mencionada](mention:user:' . $mentioned->id . ')';
        $meeting = \App\Models\Meeting::query()->create([
            'title' => 'Reunião com Menções',
            'notes' => $markdown,
            'status' => 'DRAFT',
            'created_by' => $author->id,
        ]);
        $meeting->projects()->attach($project);
        (new MentionIndexer(new MentionExtractor()))->synchronize($meeting, 'notes', $markdown, $author->id);

        $meeting->delete();
        $this->assertDatabaseCount('mentions', 0);

        $meeting->restore();

        $this->assertDatabaseHas('mentions', [
            'mentionable_id' => $meeting->id,
            'field' => 'notes',
            'mentioned_user_id' => $mentioned->id,
        ]);
    }

    private function project(User ...$users): Project
    {
        $project = Project::query()->create([
            'name' => 'Projeto de Menções',
            'slug' => 'projeto-de-mencoes-' . uniqid(),
            'status' => 'ACTIVE',
        ]);
        $project->users()->attach(collect($users)->mapWithKeys(
            fn (User $user): array => [$user->id => ['role' => 'CONTRIBUTOR']]
        ));

        return $project;
    }
}
