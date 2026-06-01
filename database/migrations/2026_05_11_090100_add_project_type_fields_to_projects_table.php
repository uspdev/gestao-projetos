<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('project_type_id')
                ->nullable()
                ->constrained('project_types')
                ->restrictOnDelete();

            $table->foreignId('parent_id')
                ->nullable()
                ->constrained('projects')
                ->nullOnDelete();

            $table->string('visibility')->default('PRIVATE');
            $table->string('permission_inheritance')->default('FULL');
            $table->string('phase')->default('PLANNING');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropForeign(['project_type_id']);
            $table->dropForeign(['parent_id']);
            $table->dropColumn([
                'project_type_id',
                'parent_id',
                'visibility',
                'permission_inheritance',
                'phase',
            ]);
        });
    }
};
