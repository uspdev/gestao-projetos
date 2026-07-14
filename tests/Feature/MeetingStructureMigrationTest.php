<?php

namespace Tests\Feature;

use App\Models\MeetingItem;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use RuntimeException;
use Tests\TestCase;

class MeetingStructureMigrationTest extends TestCase
{
    private const MIGRATION = 'database/migrations/2026_07_14_090000_expand_meetings_and_meeting_items.php';

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        $this->createLegacySchema();
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');

        parent::tearDown();
    }

    public function test_expansion_preserves_existing_values_and_allows_an_independent_item(): void
    {
        DB::table('meetings')->insert([
            'id' => 1,
            'title' => 'Reunião legada',
            'notes' => 'Anotações prévias existentes',
            'status' => 'DRAFT',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('meeting_items')->insert([
            'id' => 1,
            'meeting_id' => 1,
            'discussable_type' => 'project',
            'discussable_id' => 10,
            'order' => 1,
            'notes' => 'Anotação do item existente',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $migration = $this->migration();
        $migration->up();

        $this->assertSame('Anotações prévias existentes', DB::table('meetings')->value('notes'));
        $this->assertDatabaseHas('meeting_items', [
            'id' => 1,
            'discussable_type' => 'project',
            'discussable_id' => 10,
            'notes' => 'Anotação do item existente',
        ]);

        $this->assertTrue($this->column('meetings', 'ata')['nullable']);
        $this->assertTrue($this->column('meetings', 'transcription')['nullable']);
        $this->assertTrue($this->column('meeting_items', 'title')['nullable']);
        $this->assertTrue($this->column('meeting_items', 'discussable_type')['nullable']);
        $this->assertTrue($this->column('meeting_items', 'discussable_id')['nullable']);

        DB::table('meeting_items')->insert([
            'meeting_id' => 1,
            'title' => 'Ideia independente',
            'discussable_type' => null,
            'discussable_id' => null,
            'order' => 2,
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->assertDatabaseHas('meeting_items', [
            'meeting_id' => 1,
            'title' => 'Ideia independente',
            'discussable_type' => null,
            'discussable_id' => null,
        ]);
    }

    public function test_reversal_restores_the_legacy_structure_when_no_new_data_exists(): void
    {
        $migration = $this->migration();
        $migration->up();
        $migration->down();

        $this->assertFalse(Schema::hasColumn('meetings', 'ata'));
        $this->assertFalse(Schema::hasColumn('meetings', 'transcription'));
        $this->assertFalse(Schema::hasColumn('meeting_items', 'title'));
        $this->assertFalse($this->column('meeting_items', 'discussable_type')['nullable']);
        $this->assertFalse($this->column('meeting_items', 'discussable_id')['nullable']);
    }

    public function test_reversal_is_refused_when_a_meeting_record_was_persisted(): void
    {
        $migration = $this->migration();
        $migration->up();

        DB::table('meetings')->insert([
            'title' => 'Reunião com ata',
            'ata' => 'Conclusões da reunião',
            'status' => 'DRAFT',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Ata, Transcrição ou Item independente');

        $migration->down();
    }

    public function test_reversal_is_refused_when_an_independent_item_was_persisted(): void
    {
        $migration = $this->migration();
        $migration->up();

        DB::table('meetings')->insert([
            'id' => 1,
            'title' => 'Reunião',
            'status' => 'DRAFT',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('meeting_items')->insert([
            'meeting_id' => 1,
            'title' => 'Ideia independente',
            'discussable_type' => null,
            'discussable_id' => null,
            'order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Ata, Transcrição ou Item independente');

        $migration->down();
    }

    public function test_meeting_item_must_have_exactly_one_representation(): void
    {
        $migration = $this->migration();
        $migration->up();

        DB::table('meetings')->insert([
            'id' => 1,
            'title' => 'Reunião',
            'status' => 'DRAFT',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $linkedItem = new MeetingItem([
            'meeting_id' => 1,
            'discussable_type' => 'project',
            'discussable_id' => 10,
            'order' => 1,
        ]);
        $linkedItem->disableLogging()->save();

        $independentItem = new MeetingItem([
            'meeting_id' => 1,
            'title' => 'Ideia independente',
            'order' => 2,
        ]);
        $independentItem->disableLogging()->save();

        $this->assertDatabaseHas('meeting_items', [
            'id' => $linkedItem->id,
            'discussable_type' => 'project',
            'discussable_id' => 10,
            'title' => null,
        ]);
        $this->assertDatabaseHas('meeting_items', [
            'id' => $independentItem->id,
            'discussable_type' => null,
            'discussable_id' => null,
            'title' => 'Ideia independente',
        ]);

        $this->expectException(InvalidArgumentException::class);

        $invalidItem = new MeetingItem([
            'meeting_id' => 1,
            'order' => 3,
        ]);
        $invalidItem->disableLogging()->save();
    }

    public function test_meeting_item_cannot_have_a_link_and_an_independent_title_at_the_same_time(): void
    {
        $migration = $this->migration();
        $migration->up();

        $item = new MeetingItem([
            'meeting_id' => 1,
            'title' => 'Representação ambígua',
            'discussable_type' => 'project',
            'discussable_id' => 10,
            'order' => 1,
        ]);

        $this->expectException(InvalidArgumentException::class);

        $item->disableLogging()->save();
    }

    public function test_independent_title_must_have_at_least_three_characters(): void
    {
        $migration = $this->migration();
        $migration->up();
        $this->createMeeting();

        $item = new MeetingItem([
            'meeting_id' => 1,
            'title' => 'ab',
            'order' => 1,
        ]);

        $this->expectException(InvalidArgumentException::class);

        $item->disableLogging()->save();
    }

    public function test_linked_representation_must_use_a_configured_discussable_type(): void
    {
        $migration = $this->migration();
        $migration->up();
        $this->createMeeting();

        $item = new MeetingItem([
            'meeting_id' => 1,
            'discussable_type' => 'unknown',
            'discussable_id' => 10,
            'order' => 1,
        ]);

        $this->expectException(InvalidArgumentException::class);

        $item->disableLogging()->save();
    }

    private function createLegacySchema(): void
    {
        Schema::create('meetings', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->dateTime('scheduled_at')->nullable();
            $table->string('location')->nullable();
            $table->longText('notes')->nullable();
            $table->string('status');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('meeting_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('meetings')->cascadeOnDelete();
            $table->morphs('discussable');
            $table->unsignedInteger('order');
            $table->text('notes')->nullable();
            $table->index(['meeting_id', 'order']);
            $table->unique(['meeting_id', 'discussable_type', 'discussable_id']);
            $table->timestamps();
        });

        Schema::create('meeting_projects', function (Blueprint $table) {
            $table->foreignId('meeting_id')->constrained('meetings')->cascadeOnDelete();
            $table->unsignedBigInteger('project_id');
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    private function migration(): object
    {
        return require base_path(self::MIGRATION);
    }

    private function createMeeting(): void
    {
        DB::table('meetings')->insert([
            'id' => 1,
            'title' => 'Reunião',
            'status' => 'DRAFT',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function column(string $table, string $column): array
    {
        return collect(Schema::getColumns($table))->firstWhere('name', $column);
    }
}
