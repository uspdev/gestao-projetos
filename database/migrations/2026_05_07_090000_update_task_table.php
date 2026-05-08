<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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

            $table->timestamp('completed_at')->nullable();
        });

        DB::table('tasks')
            ->where('status','DONE')
            ->update(['completed_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('deleted_via_project');
            $table->dropColumn('completed_at');
        });
    }
};
