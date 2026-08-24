<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('links', function (Blueprint $table): void {
            $table->id();
            $table->morphs('linkable');
            $table->uuid('uuid')->unique();
            $table->string('name');
            $table->text('url');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['linkable_type', 'linkable_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('links');
    }
};
