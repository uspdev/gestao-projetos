<?php

namespace Tests\Feature;

use App\Enums\Watch\WatchEventType;
use App\Jobs\SendWatchDigest;
use App\Models\PendingWatchNotification;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class WatchCompletionTest extends TestCase
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
        $this->seedCompletedTask();
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');

        parent::tearDown();
    }

    public function test_completed_task_does_not_create_a_pending_notification(): void
    {
        PendingWatchNotification::addForWatchers(
            Task::query()->findOrFail(1),
            WatchEventType::COMMENT_CREATED,
            User::query()->findOrFail(1),
            'Novo comentário.',
            'Comentário depois da conclusão.',
            null,
        );

        $this->assertDatabaseCount('pending_watch_notifications', 0);
    }

    public function test_digest_discards_a_notification_that_became_invalid_after_task_completion(): void
    {
        $pendingId = DB::table('pending_watch_notifications')->insertGetId([
            'user_id' => 2,
            'watchable_type' => 'task',
            'watchable_id' => 1,
            'event_type' => WatchEventType::COMMENT_CREATED->value,
            'actor_id' => 1,
            'title' => 'Tarefa concluída',
            'summary' => 'Novo comentário.',
            'details' => null,
            'url' => null,
            'occurred_at' => now()->subMinute(),
            'send_after' => now()->subSecond(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        Mail::fake();

        (new SendWatchDigest($pendingId))->handle();

        Mail::assertNothingSent();
        $this->assertDatabaseCount('pending_watch_notifications', 0);
    }

    private function seedCompletedTask(): void
    {
        $now = now();

        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Autor', 'email' => 'autor@example.test', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'name' => 'Acompanhante', 'email' => 'acompanhante@example.test', 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('tasks')->insert([
            'id' => 1,
            'project_id' => 1,
            'title' => 'Tarefa concluída',
            'status' => 'DONE',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('watches')->insert([
            'user_id' => 2,
            'watchable_type' => 'task',
            'watchable_id' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->timestamps();
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->string('title');
            $table->string('status');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('watches', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('watchable_type');
            $table->unsignedBigInteger('watchable_id');
            $table->timestamps();
            $table->unique(['user_id', 'watchable_type', 'watchable_id']);
        });

        Schema::create('pending_watch_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('watchable_type');
            $table->unsignedBigInteger('watchable_id');
            $table->string('event_type');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('title');
            $table->text('summary');
            $table->text('details')->nullable();
            $table->text('url')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamp('send_after');
            $table->timestamps();
        });
    }
}
