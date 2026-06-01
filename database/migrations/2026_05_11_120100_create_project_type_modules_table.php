<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_type_modules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_type_id');
            $table->foreignId('module_id')->constrained('modules')->cascadeOnDelete();
            $table->boolean('enabled')->default(true);
            $table->boolean('required')->default(false);
            $table->boolean('editable')->default(true);
            $table->json('config')->nullable();
            $table->timestamps();

            $table->unique(['project_type_id', 'module_id']);
            $table->index('project_type_id');

            if (Schema::hasTable('project_types')) {
                $table->foreign('project_type_id')
                    ->references('id')
                    ->on('project_types')
                    ->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_type_modules');
    }
};
