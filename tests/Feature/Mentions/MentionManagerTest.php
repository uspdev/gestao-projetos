<?php

namespace Tests\Feature\Mentions;

use App\Models\Comment;
use App\Models\Mention;
use App\Models\Project;
use App\Models\User;
use App\Services\Mentions\MentionManager;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MentionManagerTest extends TestCase
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
        Schema::create('comments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id');
            $table->string('commentable_type');
            $table->unsignedBigInteger('commentable_id');
            $table->foreignId('parent_id')->nullable();
            $table->text('text');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('model_has_roles', function (Blueprint $table): void {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });
        Schema::create('model_has_permissions', function (Blueprint $table): void {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });

        (require database_path('migrations/2026_07_23_090000_create_mentions_table.php'))->up();
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');

        parent::tearDown();
    }

    public function test_it_indexes_a_user_mention_through_polymorphic_source_and_target_relationships(): void
    {
        $author = User::query()->create(['name' => 'Pessoa autora']);
        $mentioned = User::query()->create(['name' => 'Pessoa mencionada']);
        $project = Project::query()->create([
            'name' => 'Projeto de Menções',
            'slug' => 'projeto-de-mencoes',
            'status' => 'ACTIVE',
        ]);
        $project->users()->attach([
            $author->id => ['role' => 'CONTRIBUTOR'],
            $mentioned->id => ['role' => 'CONTRIBUTOR'],
        ]);

        app(MentionManager::class)->synchronize(
            $project,
            'description',
            '@[Pessoa mencionada](mention:user:' . $mentioned->id . ')'
        );

        $mention = Mention::query()->firstOrFail();

        $this->assertSame('project', $mention->source_type);
        $this->assertSame((string) $project->id, (string) $mention->source_id);
        $this->assertSame('description', $mention->source_field);
        $this->assertSame('user', $mention->target_type);
        $this->assertSame((string) $mentioned->id, (string) $mention->target_id);
        $this->assertTrue($mention->source->is($project));
        $this->assertTrue($mention->target->is($mentioned));
        $this->assertTrue($project->outgoingMentions()->whereKey($mention->id)->exists());
        $this->assertTrue($mentioned->incomingMentions()->whereKey($mention->id)->exists());
    }

    public function test_the_mentions_table_contains_only_the_rebuildable_polymorphic_index(): void
    {
        foreach (['source_type', 'source_id', 'source_field', 'target_type', 'target_id'] as $column) {
            $this->assertTrue(Schema::hasColumn('mentions', $column), $column);
        }

        foreach (['mentionable_type', 'mentionable_id', 'field', 'mentioned_user_id', 'created_by', 'created_at', 'updated_at'] as $column) {
            $this->assertFalse(Schema::hasColumn('mentions', $column), $column);
        }
    }

    public function test_it_rejects_a_malformed_mention_before_synchronizing_the_field(): void
    {
        $author = User::query()->create(['name' => 'Pessoa autora']);
        $project = Project::query()->create([
            'name' => 'Projeto de Menções inválidas',
            'slug' => 'projeto-de-mencoes-invalidas',
            'status' => 'ACTIVE',
        ]);
        $project->users()->attach($author, ['role' => 'CONTRIBUTOR']);

        $this->expectExceptionObject(ValidationException::withMessages([
            'description' => 'Uma ou mais Menções estão malformadas.',
        ]));

        app(MentionManager::class)->validateAllMentions(
            $project,
            'description',
            '@[Pessoa inválida](mention:user:zero)'
        );
    }

    public function test_it_rejects_a_class_name_instead_of_a_stable_target_alias(): void
    {
        $author = User::query()->create(['name' => 'Pessoa autora']);
        $project = Project::query()->create([
            'name' => 'Projeto com classe inválida',
            'slug' => 'projeto-com-classe-invalida',
            'status' => 'ACTIVE',
        ]);
        $project->users()->attach($author, ['role' => 'CONTRIBUTOR']);

        $this->expectException(ValidationException::class);

        app(MentionManager::class)->validateAllMentions(
            $project,
            'description',
            '@[Pessoa inválida](mention:App\\Models\\User:1)'
        );
    }

    public function test_it_clears_and_rebuilds_comment_mentions_when_activity_changes(): void
    {
        $author = User::query()->create(['name' => 'Pessoa autora']);
        $mentioned = User::query()->create(['name' => 'Pessoa mencionada']);
        $project = Project::query()->create([
            'name' => 'Projeto comentado',
            'slug' => 'projeto-comentado',
            'status' => 'ACTIVE',
        ]);
        $project->users()->attach([
            $author->id => ['role' => 'CONTRIBUTOR'],
            $mentioned->id => ['role' => 'CONTRIBUTOR'],
        ]);
        $comment = Comment::query()->create([
            'user_id' => $author->id,
            'commentable_type' => 'project',
            'commentable_id' => $project->id,
            'text' => '@[Pessoa mencionada](mention:user:' . $mentioned->id . ')',
            'is_active' => true,
        ]);
        $manager = app(MentionManager::class);

        $manager->synchronize($comment, 'text', $comment->text);
        $this->assertDatabaseCount('mentions', 1);

        $comment->update(['is_active' => false]);
        $this->assertDatabaseCount('mentions', 0);

        $comment->update(['is_active' => true]);

        $this->assertDatabaseHas('mentions', [
            'source_type' => 'comment',
            'source_id' => $comment->id,
            'source_field' => 'text',
            'target_type' => 'user',
            'target_id' => $mentioned->id,
        ]);
    }

    public function test_it_removes_incoming_mentions_when_a_user_is_deleted(): void
    {
        $author = User::query()->create(['name' => 'Pessoa autora']);
        $mentioned = User::query()->create(['name' => 'Pessoa mencionada']);
        $project = Project::query()->create([
            'name' => 'Projeto com destino removido',
            'slug' => 'projeto-com-destino-removido',
            'status' => 'ACTIVE',
        ]);
        $project->users()->attach([
            $author->id => ['role' => 'CONTRIBUTOR'],
            $mentioned->id => ['role' => 'CONTRIBUTOR'],
        ]);
        $manager = app(MentionManager::class);

        $manager->synchronize(
            $project,
            'description',
            '@[Pessoa mencionada](mention:user:' . $mentioned->id . ')'
        );
        $project->update([
            'description' => '@[Pessoa mencionada](mention:user:' . $mentioned->id . ')',
        ]);

        $mentioned->delete();

        $this->assertDatabaseCount('mentions', 0);
        $this->assertSame(
            '@[Pessoa mencionada](mention:user:' . $mentioned->id . ')',
            $project->fresh()->description
        );
    }

    public function test_it_uses_the_same_validation_message_for_missing_and_ineligible_new_users(): void
    {
        $author = User::query()->create(['name' => 'Pessoa autora']);
        $ineligible = User::query()->create(['name' => 'Pessoa fora do contexto']);
        $project = Project::query()->create([
            'name' => 'Projeto de validação',
            'slug' => 'projeto-de-validacao',
            'status' => 'ACTIVE',
        ]);
        $project->users()->attach($author, ['role' => 'CONTRIBUTOR']);
        $manager = app(MentionManager::class);

        $messages = [];

        foreach ([$ineligible->id, 99999] as $targetId) {
            try {
                $manager->validateAllMentions(
                    $project,
                    'description',
                    '@[Pessoa](mention:user:' . $targetId . ')'
                );
            } catch (ValidationException $exception) {
                $messages[] = $exception->errors()['description'][0];
            }
        }

        $this->assertSame([
            'Uma ou mais Menções não existem ou não são permitidas neste contexto.',
            'Uma ou mais Menções não existem ou não são permitidas neste contexto.',
        ], $messages);
    }

    public function test_it_indexes_only_explicit_mentions_and_ignores_code_and_common_links(): void
    {
        $author = User::query()->create(['name' => 'Pessoa autora']);
        $mentioned = User::query()->create(['name' => 'Pessoa mencionada']);
        $project = Project::query()->create([
            'name' => 'Projeto de links',
            'slug' => 'projeto-de-links',
            'status' => 'ACTIVE',
        ]);
        $project->users()->attach([
            $author->id => ['role' => 'CONTRIBUTOR'],
            $mentioned->id => ['role' => 'CONTRIBUTOR'],
        ]);
        $markdown = implode(' ', [
            '`@[Pessoa em código](mention:user:' . $mentioned->id . ')`',
            '[Pessoa em link](/users/' . $mentioned->id . ')',
        ]);
        $manager = app(MentionManager::class);

        $manager->validateAllMentions($project, 'description', $markdown);
        $manager->synchronize($project, 'description', $markdown);

        $this->assertDatabaseCount('mentions', 0);
    }
}
