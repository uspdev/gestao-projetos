<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MeetingWatchMigrationTest extends TestCase
{
    private const MIGRATION = 'database/migrations/2026_08_04_120000_enable_watches_for_open_meetings.php';

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

    public function test_it_enables_watches_for_members_of_open_legacy_meetings_without_duplicating_existing_watches(): void
    {
        $now = now();

        DB::table('meetings')->insert([
            ['id' => 1, 'title' => 'Agendada', 'status' => 'SCHEDULED', 'created_by' => 1, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['id' => 2, 'title' => 'Em andamento', 'status' => 'ONGOING', 'created_by' => 2, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['id' => 3, 'title' => 'Concluída', 'status' => 'COMPLETED', 'created_by' => 3, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
            ['id' => 4, 'title' => 'Removida', 'status' => 'SCHEDULED', 'created_by' => 4, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => $now],
            ['id' => 5, 'title' => 'Sem criador', 'status' => 'DRAFT', 'created_by' => null, 'created_at' => $now, 'updated_at' => $now, 'deleted_at' => null],
        ]);
        DB::table('meeting_projects')->insert([
            ['meeting_id' => 1, 'project_id' => 10],
            ['meeting_id' => 2, 'project_id' => 10],
            ['meeting_id' => 2, 'project_id' => 11],
            ['meeting_id' => 3, 'project_id' => 12],
            ['meeting_id' => 4, 'project_id' => 13],
            ['meeting_id' => 5, 'project_id' => 14],
        ]);
        DB::table('project_user')->insert([
            ['project_id' => 10, 'user_id' => 1],
            ['project_id' => 10, 'user_id' => 2],
            ['project_id' => 11, 'user_id' => 2],
            ['project_id' => 11, 'user_id' => 5],
            ['project_id' => 12, 'user_id' => 3],
            ['project_id' => 13, 'user_id' => 4],
            ['project_id' => 14, 'user_id' => 6],
        ]);
        DB::table('watches')->insert([
            'user_id' => 1,
            'watchable_type' => 'meeting',
            'watchable_id' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $migration = require base_path(self::MIGRATION);
        $migration->up();
        $migration->up();

        $this->assertDatabaseCount('watches', 6);
        $this->assertDatabaseHas('watches', [
            'user_id' => 1,
            'watchable_type' => 'meeting',
            'watchable_id' => 1,
        ]);
        $this->assertDatabaseHas('watches', [
            'user_id' => 2,
            'watchable_type' => 'meeting',
            'watchable_id' => 1,
        ]);
        $this->assertDatabaseHas('watches', ['user_id' => 1, 'watchable_id' => 2]);
        $this->assertDatabaseHas('watches', ['user_id' => 2, 'watchable_id' => 2]);
        $this->assertDatabaseHas('watches', ['user_id' => 5, 'watchable_id' => 2]);
        $this->assertDatabaseHas('watches', ['user_id' => 6, 'watchable_id' => 5]);
        $this->assertDatabaseMissing('watches', ['watchable_id' => 3]);
        $this->assertDatabaseMissing('watches', ['watchable_id' => 4]);

        $migration->down();

        $this->assertDatabaseCount('watches', 6);
    }

    private function createSchema(): void
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('status');
            $table->unsignedBigInteger('created_by')->nullable();
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

        Schema::create('meeting_projects', function (Blueprint $table) {
            $table->unsignedBigInteger('meeting_id');
            $table->unsignedBigInteger('project_id');
        });

        Schema::create('project_user', function (Blueprint $table) {
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('user_id');
        });
    }
}
