<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Atualiza os dados existentes no banco
        DB::table('project_user')
            ->where('role', 'MEMBER')
            ->update(['role' => 'CONTRIBUTOR']);

        // 2. Altera a estrutura da tabela para o novo valor default
        Schema::table('project_user', function (Blueprint $table) {
            $table->string('role')->default('CONTRIBUTOR')->change();
        });
    }

    //faz o inverso caso precise de rollback da migration
    public function down(): void
    {
        // 1. Reverte a estrutura da tabela para o valor default antigo
        Schema::table('project_user', function (Blueprint $table) {
            $table->string('role')->default('MEMBER')->change();
        });

        // 2. Reverte os dados existentes
        DB::table('project_user')
            ->where('role', 'CONTRIBUTOR')
            ->update(['role' => 'MEMBER']);
    }
};