<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->boolean('deleted_via_project')
                  ->default(false)
                  ->after('deleted_by')
                  ->comment('Flag para identificar tasks que sofreram soft delete em cascata via Project. Evita restaurar indevidamente tasks que já haviam sido deletadas manualmente antes da exclusão do projeto.');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('deleted_via_project');
        });
    }
};