<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const LEGACY_HTML = "Estrutura organizacional para agrupar múltiplos projetos relacionados, permitindo gestão centralizada e visão\n            consolidada.<br>\n            Dentro do <b>container</b> pode-se criar quaisquer outros tipos de projetos.";

    private const MARKDOWN = "Estrutura organizacional para agrupar múltiplos projetos relacionados, permitindo gestão centralizada e visão consolidada.\n\nDentro do **container** pode-se criar quaisquer outros tipos de projetos.";

    public function up(): void
    {
        if (! Schema::hasTable('project_types') || ! Schema::hasColumn('project_types', 'description')) {
            return;
        }

        DB::table('project_types')
            ->where('slug', 'organizacional')
            ->where('description', self::LEGACY_HTML)
            ->update(['description' => self::MARKDOWN]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('project_types') || ! Schema::hasColumn('project_types', 'description')) {
            return;
        }

        DB::table('project_types')
            ->where('slug', 'organizacional')
            ->where('description', self::MARKDOWN)
            ->update(['description' => self::LEGACY_HTML]);
    }
};
