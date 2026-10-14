<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uspdev_api_keys', function (Blueprint $table): void {
            $table->id();
            $table->morphs('owner');
            $table->string('name');
            $table->string('purpose')->index();
            $table->string('role')->index();
            $table->string('prefix')->unique();
            $table->string('secret_hash');
            $table->unsignedBigInteger('access_count')->default(0);
            $table->timestamp('last_used_at')->nullable();
            $table->string('last_used_ip', 45)->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('revoked_at')->nullable()->index();
            $table->unsignedBigInteger('revoked_by')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uspdev_api_keys');
    }
};
