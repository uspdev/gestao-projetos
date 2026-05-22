<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_type_phases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_type_id')->constrained('project_types')->cascadeOnDelete();
            $table->foreignId('phase_id')->constrained('phases')->cascadeOnDelete();
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_initial')->default(false);
            $table->boolean('is_final')->default(false);
            $table->timestamps();

            $table->unique(['project_type_id', 'phase_id']);
            $table->index(['project_type_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_type_phases');
    }
};
