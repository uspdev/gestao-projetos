<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->longText('ata')->nullable();
            $table->longText('transcription')->nullable();
        });

        Schema::table('meeting_items', function (Blueprint $table) {
            $table->string('title')->nullable()->after('discussable_id');
            $table->string('discussable_type')->nullable()->change();
            $table->unsignedBigInteger('discussable_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        if ($this->hasDataThatWouldBeLost()) {
            throw new RuntimeException(
                'A migração não pode ser revertida enquanto houver Ata, Transcrição ou Item independente persistido.'
            );
        }

        Schema::table('meeting_items', function (Blueprint $table) {
            $table->string('discussable_type')->nullable(false)->change();
            $table->unsignedBigInteger('discussable_id')->nullable(false)->change();
            $table->dropColumn('title');
        });

        Schema::table('meetings', function (Blueprint $table) {
            $table->dropColumn(['ata', 'transcription']);
        });
    }

    private function hasDataThatWouldBeLost(): bool
    {
        $hasMeetingRecords = DB::table('meetings')
            ->whereNotNull('ata')
            ->orWhereNotNull('transcription')
            ->exists();

        if ($hasMeetingRecords) {
            return true;
        }

        return DB::table('meeting_items')
            ->whereNotNull('title')
            ->orWhereNull('discussable_type')
            ->orWhereNull('discussable_id')
            ->exists();
    }
};
