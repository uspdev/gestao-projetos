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
            $table->morphs('mentionable');
            $table->string('field');
            $table->foreignId('mentioned_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(
                ['mentionable_type', 'mentionable_id', 'field', 'mentioned_user_id'],
                'mentions_source_field_user_unique'
            );
            $table->index(['mentioned_user_id', 'mentionable_type', 'mentionable_id'], 'mentions_user_source_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mentions');
    }
};
