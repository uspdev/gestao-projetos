<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meeting_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('meeting_id')->constrained('meetings')->cascadeOnDelete();
            $table->morphs('discussable');
            $table->unsignedInteger('order');

            $table->index(['meeting_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meeting_items');
    }
};
