<?php

namespace Tests\Feature;

use App\Mail\MeetingWatchUpdate;
use App\Models\Meeting;
use App\Models\PendingWatchNotification;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MeetingWatchNotificationTest extends TestCase
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

    public function test_meeting_updates_queue_an_individual_notification_instead_of_a_digest(): void
    {
        $now = now();
        DB::table('users')->insert([
            ['id' => 1, 'name' => 'Autor', 'email' => 'autor@example.test', 'created_at' => $now, 'updated_at' => $now],
            ['id' => 2, 'name' => 'Acompanhante', 'email' => 'acompanhante@example.test', 'created_at' => $now, 'updated_at' => $now],
        ]);
        DB::table('meetings')->insert([
            'id' => 1,
            'title' => 'Reunião de planejamento',
            'status' => 'SCHEDULED',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        DB::table('watches')->insert([
            'user_id' => 2,
            'watchable_type' => 'meeting',
            'watchable_id' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        Mail::fake();

        PendingWatchNotification::dispatchMeetingUpdateForWatchers(
            Meeting::query()->findOrFail(1),
            User::query()->findOrFail(1),
            'Reunião atualizada.',
        );

        Mail::assertQueued(MeetingWatchUpdate::class, function (MeetingWatchUpdate $mail): bool {
            return $mail->recipient->id === 2
                && $mail->actor->id === 1
                && $mail->meeting->id === 1
                && $mail->summary === 'Reunião atualizada.';
        });
    }

    private function createSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email');
            $table->timestamps();
        });

        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
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
    }
}
