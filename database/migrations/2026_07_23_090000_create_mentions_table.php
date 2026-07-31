<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mentions', function (Blueprint $table): void {
            $table->id();
            $table->string('source_type', 50);
            $table->unsignedBigInteger('source_id');
            $table->string('source_field', 100);
            $table->string('target_type', 50);
            $table->string('target_id', 191);

            $table->index(['source_type', 'source_id'], 'mentions_source_index');

            $table->unique(
                ['source_type', 'source_id', 'source_field', 'target_type', 'target_id'],
                'mentions_source_field_target_unique'
            );
            $table->index(
                ['source_type', 'source_id', 'source_field'],
                'mentions_source_field_index'
            );
            $table->index(['target_type', 'target_id'], 'mentions_target_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mentions');
    }
};
