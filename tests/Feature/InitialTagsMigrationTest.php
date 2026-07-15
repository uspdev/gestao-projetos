<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Activitylog\ActivityLogStatus;
use Tests\TestCase;

class InitialTagsMigrationTest extends TestCase
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
        app(ActivityLogStatus::class)->enable();
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');

        parent::tearDown();
    }

    public function test_tag_seeds_do_not_require_activity_log_table(): void
    {
        $this->runMigration('2026_04_22_172311_create_tag_tables.php');

        $this->assertFalse(Schema::hasTable('activity_log'));

        $this->runMigration('2026_04_24_173347_seed_initial_task_tags.php');
        $this->runMigration('2026_04_24_174812_seed_initial_project_tags.php');

        $this->assertFalse(Schema::hasTable('activity_log'));
        $this->assertSame(5, DB::table('tags')->where('type', 'tasks')->count());
        $this->assertSame(4, DB::table('tags')->where('type', 'projects')->count());

        $firstTaskTag = DB::table('tags')
            ->where('type', 'tasks')
            ->orderBy('order_column')
            ->first();

        $this->assertSame(1, $firstTaskTag->order_column);
        $this->assertSame('correcao', json_decode($firstTaskTag->slug, true)['pt_BR']);
    }

    private function runMigration(string $filename): void
    {
        $migration = require base_path("database/migrations/{$filename}");

        $migration->up();
    }
}
