<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProjectTypeMarkdownMigrationTest extends TestCase
{
    private const MIGRATION = 'database/migrations/2026_07_20_120000_convert_organizational_project_type_description_to_markdown.php';

    private const LEGACY_HTML = "Estrutura organizacional para agrupar múltiplos projetos relacionados, permitindo gestão centralizada e visão\n            consolidada.<br>\n            Dentro do <b>container</b> pode-se criar quaisquer outros tipos de projetos.";

    private const MARKDOWN = "Estrutura organizacional para agrupar múltiplos projetos relacionados, permitindo gestão centralizada e visão consolidada.\n\nDentro do **container** pode-se criar quaisquer outros tipos de projetos.";

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
        ]);

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        Schema::create('project_types', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->text('description')->nullable();
        });
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');

        parent::tearDown();
    }

    public function test_it_converts_only_the_known_legacy_html_and_is_idempotent(): void
    {
        DB::table('project_types')->insert([
            ['slug' => 'organizacional', 'description' => self::LEGACY_HTML],
            ['slug' => 'outro', 'description' => self::LEGACY_HTML],
            ['slug' => 'personalizado', 'description' => '<b>Conteúdo próprio</b>'],
        ]);

        $migration = require base_path(self::MIGRATION);
        $migration->up();
        $migration->up();

        $this->assertSame(self::MARKDOWN, DB::table('project_types')->where('slug', 'organizacional')->value('description'));
        $this->assertSame(self::LEGACY_HTML, DB::table('project_types')->where('slug', 'outro')->value('description'));
        $this->assertSame('<b>Conteúdo próprio</b>', DB::table('project_types')->where('slug', 'personalizado')->value('description'));
    }

    public function test_it_safely_reverses_only_the_exact_converted_value(): void
    {
        DB::table('project_types')->insert([
            ['slug' => 'organizacional', 'description' => self::LEGACY_HTML],
            ['slug' => 'outro', 'description' => self::MARKDOWN],
        ]);

        $migration = require base_path(self::MIGRATION);
        $migration->up();
        $migration->down();

        $this->assertSame(self::LEGACY_HTML, DB::table('project_types')->where('slug', 'organizacional')->value('description'));
        $this->assertSame(self::MARKDOWN, DB::table('project_types')->where('slug', 'outro')->value('description'));
    }
}
