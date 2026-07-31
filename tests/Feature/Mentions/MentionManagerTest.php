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
        Schema::create('tasks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('project_id');
            $table->string('title');
            $table->string('status');
            $table->boolean('deleted_via_project')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('tags', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->integer('order_column')->nullable();
        });
        Schema::create('taggables', function (Blueprint $table): void {
            $table->foreignId('tag_id');
            $table->string('taggable_type');
            $table->unsignedBigInteger('taggable_id');
        });
        Schema::create('media', function (Blueprint $table): void {
            $table->id();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->uuid('uuid')->unique();
            $table->string('collection_name');
            $table->string('name');
            $table->string('original_name');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->string('disk');
            $table->string('conversions_disk')->nullable();
            $table->unsignedBigInteger('size');
            $table->json('manipulations');
            $table->json('custom_properties');
            $table->json('generated_conversions');
            $table->json('responsive_images');
            $table->unsignedInteger('order_column')->nullable();
            $table->foreignId('uploaded_by')->nullable();
            $table->nullableTimestamps();
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
        Schema::create('role_has_permissions', function (Blueprint $table): void {
            $table->foreignId('permission_id');
            $table->foreignId('role_id');
        });
        DB::table('permissions')->insert([
            'name' => 'admin',
            'guard_name' => 'senhaunica',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
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

    public function test_it_indexes_a_project_mention_with_its_stable_id_and_incoming_relationship(): void
    {
        $author = User::query()->create(['name' => 'Pessoa autora']);
        $source = Project::query()->create([
            'name' => 'Projeto fonte',
            'slug' => 'projeto-fonte',
            'status' => 'ACTIVE',
        ]);
        $target = Project::query()->create([
            'name' => 'Projeto mencionado',
            'slug' => 'projeto-mencionado',
            'status' => 'ACTIVE',
        ]);
        $source->users()->attach($author, ['role' => 'CONTRIBUTOR']);
        $target->users()->attach($author, ['role' => 'VIEWER']);

        $this->actingAs($author);

        app(MentionManager::class)->synchronize(
            $source,
            'description',
            '@[Nome histórico](mention:project:' . $target->id . ')'
        );

        $mention = Mention::query()->firstOrFail();

        $this->assertSame('project', $mention->target_type);
        $this->assertSame((string) $target->id, (string) $mention->target_id);
        $this->assertTrue($mention->target->is($target));
        $this->assertTrue($target->incomingMentions()->whereKey($mention->id)->exists());
    }

    public function test_it_searches_contextual_and_other_visible_projects_with_an_explicit_project_filter(): void
    {
        $author = User::query()->create(['name' => 'Pessoa autora']);
        $context = Project::query()->create([
            'name' => 'Projeto contextual',
            'slug' => 'projeto-contextual',
            'status' => 'ACTIVE',
        ]);
        $otherVisible = Project::query()->create([
            'name' => 'Projeto global visível',
            'slug' => 'projeto-global-visivel',
            'status' => 'ACTIVE',
        ]);
        $hidden = Project::query()->create([
            'name' => 'Projeto global oculto',
            'slug' => 'projeto-global-oculto',
            'status' => 'ACTIVE',
        ]);
        $context->users()->attach($author, ['role' => 'CONTRIBUTOR']);
        $otherVisible->users()->attach($author, ['role' => 'VIEWER']);
        $comment = Comment::query()->create([
            'user_id' => $author->id,
            'commentable_type' => 'project',
            'commentable_id' => $context->id,
            'text' => 'Comentário',
            'is_active' => true,
        ]);

        $results = app(MentionManager::class)->search($comment, '', $author, 'project');

        $this->assertSame(
            [$context->id, $otherVisible->id],
            $results->pluck('id')->all()
        );
        $this->assertSame(
            ['project', 'project'],
            $results->pluck('type')->all()
        );
        $this->assertFalse($results->contains('id', $hidden->id));
    }

    public function test_the_autocomplete_route_returns_project_results_for_a_comment_context(): void
    {
        $author = User::query()->create(['name' => 'Pessoa autora']);
        $context = Project::query()->create([
            'name' => 'Projeto do comentário',
            'slug' => 'projeto-do-comentario',
            'status' => 'ACTIVE',
        ]);
        $context->users()->attach($author, ['role' => 'CONTRIBUTOR']);
        $comment = Comment::query()->create([
            'user_id' => $author->id,
            'commentable_type' => 'project',
            'commentable_id' => $context->id,
            'text' => 'Comentário',
            'is_active' => true,
        ]);

        $this->actingAs($author)
            ->getJson(route('mentions.selectable', [
                'context_type' => 'comment',
                'commentable_type' => 'project',
                'commentable_id' => $context->id,
                'filter' => 'project',
            ]))
            ->assertOk()
            ->assertJsonPath('results.0.id', $context->id)
            ->assertJsonPath('results.0.type', 'project')
            ->assertJsonPath('results.0.type_label', 'Projeto');
    }

    public function test_the_project_description_route_synchronizes_a_project_mention(): void
    {
        $author = User::query()->create(['name' => 'Pessoa autora']);
        $source = Project::query()->create([
            'name' => 'Projeto fonte web',
            'slug' => 'projeto-fonte-web',
            'status' => 'ACTIVE',
        ]);
        $target = Project::query()->create([
            'name' => 'Projeto destino web',
            'slug' => 'projeto-destino-web',
            'status' => 'ACTIVE',
        ]);
        $source->users()->attach($author, ['role' => 'ADMIN']);
        $target->users()->attach($author, ['role' => 'VIEWER']);
        $markdown = '@[Projeto destino](mention:project:' . $target->id . ')';

        $this->actingAs($author)
            ->patch(route('projects.updateDescription', $source), ['description' => $markdown])
            ->assertRedirect();

        $this->assertDatabaseHas('mentions', [
            'source_type' => 'project',
            'source_id' => $source->id,
            'source_field' => 'description',
            'target_type' => 'project',
            'target_id' => $target->id,
        ]);
    }

    public function test_it_rejects_a_project_mention_to_the_project_source_itself(): void
    {
        $author = User::query()->create(['name' => 'Pessoa autora']);
        $project = Project::query()->create([
            'name' => 'Projeto autorreferente',
            'slug' => 'projeto-autorreferente',
            'status' => 'ACTIVE',
        ]);
        $project->users()->attach($author, ['role' => 'CONTRIBUTOR']);

        $this->actingAs($author);

        $this->expectExceptionObject(ValidationException::withMessages([
            'description' => 'Uma ou mais Menções não existem ou não são permitidas neste contexto.',
        ]));

        app(MentionManager::class)->validateAllMentions(
            $project,
            'description',
            '@[Projeto autorreferente](mention:project:' . $project->id . ')'
        );
    }

    public function test_it_presents_a_project_with_its_current_name_and_slug_without_rewriting_markdown(): void
    {
        $author = User::query()->create(['name' => 'Pessoa autora']);
        $source = Project::query()->create([
            'name' => 'Projeto fonte da leitura',
            'slug' => 'projeto-fonte-da-leitura',
            'status' => 'ACTIVE',
        ]);
        $target = Project::query()->create([
            'name' => 'Nome antigo',
            'slug' => 'slug-antigo',
            'status' => 'ACTIVE',
        ]);
        $source->users()->attach($author, ['role' => 'CONTRIBUTOR']);
        $target->users()->attach($author, ['role' => 'VIEWER']);
        $markdown = '@[Rótulo histórico](mention:project:' . $target->id . ')';

        $this->actingAs($author);
        $source->update(['description' => $markdown]);
        app(MentionManager::class)->synchronize($source, 'description', $markdown);

        $target->update([
            'name' => 'Nome atual',
            'slug' => 'slug-atual',
        ]);

        $presentation = app(MentionManager::class)->present('project', (string) $target->id, $author);

        $this->assertSame('available', $presentation['status']);
        $this->assertSame('Nome atual', $presentation['label']);
        $this->assertSame(route('projects.show', $target->fresh()), $presentation['url']);
        $this->assertSame('projeto: Nome atual', $presentation['accessible_name']);
        $this->assertSame($markdown, $source->fresh()->description);
    }

    public function test_it_hides_a_project_name_when_the_reader_cannot_view_it_and_distinguishes_missing_targets(): void
    {
        $owner = User::query()->create(['name' => 'Pessoa autorizada']);
        $reader = User::query()->create(['name' => 'Pessoa sem acesso']);
        $project = Project::query()->create([
            'name' => 'Projeto restrito',
            'slug' => 'projeto-restrito',
            'status' => 'ACTIVE',
        ]);
        $project->users()->attach($owner, ['role' => 'CONTRIBUTOR']);
        $manager = app(MentionManager::class);

        $forbidden = $manager->present('project', (string) $project->id, $reader);
        $missing = $manager->present('project', '99999', $reader);

        $this->assertSame([
            'status' => 'forbidden',
            'type' => 'projeto',
            'message' => 'Menção a projeto: você não tem permissão para visualizar',
        ], $forbidden);
        $this->assertSame([
            'status' => 'missing',
            'type' => 'projeto',
            'message' => 'Menção a projeto: destino não encontrado',
        ], $missing);
    }

    public function test_it_keeps_a_project_relation_during_soft_deletion_and_resolves_it_after_restoration(): void
    {
        $author = User::query()->create(['name' => 'Pessoa autora']);
        $source = Project::query()->create([
            'name' => 'Projeto fonte restaurável',
            'slug' => 'projeto-fonte-restauravel',
            'status' => 'ACTIVE',
        ]);
        $target = Project::query()->create([
            'name' => 'Projeto restaurável',
            'slug' => 'projeto-restauravel',
            'status' => 'ACTIVE',
        ]);
        $source->users()->attach($author, ['role' => 'CONTRIBUTOR']);
        $target->users()->attach($author, ['role' => 'VIEWER']);
        $source->update([
            'description' => '@[Projeto restaurável](mention:project:' . $target->id . ')',
        ]);
        $this->actingAs($author);
        app(MentionManager::class)->synchronize($source, 'description', $source->description);

        DB::table('projects')->where('id', $target->id)->update(['deleted_at' => now()]);
        $target = Project::withTrashed()->findOrFail($target->id);

        $this->assertDatabaseCount('mentions', 1);
        $this->assertTrue($target->trashed());
        $this->assertSame('missing', app(MentionManager::class)->present('project', (string) $target->id, $author)['status']);

        DB::table('projects')->where('id', $target->id)->update(['deleted_at' => null]);

        $this->assertSame('available', app(MentionManager::class)->present('project', (string) $target->id, $author)['status']);
        $this->assertDatabaseHas('mentions', [
            'target_type' => 'project',
            'target_id' => $target->id,
        ]);
    }

    public function test_it_removes_project_incoming_relations_after_permanent_deletion_without_rewriting_the_source(): void
    {
        $author = User::query()->create(['name' => 'Pessoa autora']);
        $source = Project::query()->create([
            'name' => 'Projeto fonte permanente',
            'slug' => 'projeto-fonte-permanente',
            'status' => 'ACTIVE',
        ]);
        $target = Project::query()->create([
            'name' => 'Projeto excluído definitivamente',
            'slug' => 'projeto-excluido-definitivamente',
            'status' => 'ACTIVE',
        ]);
        $source->users()->attach($author, ['role' => 'CONTRIBUTOR']);
        $target->users()->attach($author, ['role' => 'VIEWER']);
        $markdown = '@[Projeto histórico](mention:project:' . $target->id . ')';
        $source->update(['description' => $markdown]);
        $this->actingAs($author);
        app(MentionManager::class)->synchronize($source, 'description', $markdown);

        $target->forceDelete();

        $this->assertDatabaseCount('mentions', 0);
        $this->assertSame($markdown, $source->fresh()->description);
        $this->assertSame(
            'missing',
            app(MentionManager::class)->present('project', (string) $target->id, $author)['status']
        );
    }

    public function test_authorized_incoming_project_queries_do_not_reveal_mentions_from_hidden_sources(): void
    {
        $writer = User::query()->create(['name' => 'Pessoa autora']);
        $reader = User::query()->create(['name' => 'Pessoa leitora']);
        $target = Project::query()->create([
            'name' => 'Projeto destino',
            'slug' => 'projeto-destino',
            'status' => 'ACTIVE',
        ]);
        $visibleSource = Project::query()->create([
            'name' => 'Fonte visível',
            'slug' => 'fonte-visivel',
            'status' => 'ACTIVE',
        ]);
        $hiddenSource = Project::query()->create([
            'name' => 'Fonte oculta',
            'slug' => 'fonte-oculta',
            'status' => 'ACTIVE',
        ]);
        $target->users()->attach([
            $writer->id => ['role' => 'VIEWER'],
            $reader->id => ['role' => 'VIEWER'],
        ]);
        $visibleSource->users()->attach([
            $writer->id => ['role' => 'CONTRIBUTOR'],
            $reader->id => ['role' => 'VIEWER'],
        ]);
        $hiddenSource->users()->attach($writer, ['role' => 'CONTRIBUTOR']);
        $markdown = '@[Projeto destino](mention:project:' . $target->id . ')';
        $visibleSource->update(['description' => $markdown]);
        $hiddenSource->update(['description' => $markdown]);

        $this->actingAs($writer);
        $manager = app(MentionManager::class);
        $manager->synchronize($visibleSource, 'description', $markdown);
        $manager->synchronize($hiddenSource, 'description', $markdown);

        $incoming = $manager->incomingMentions($target, $reader);

        $this->assertSame([$visibleSource->id], $incoming->pluck('source_id')->all());
    }
}
