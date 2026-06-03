<?php

use App\Enums\Meeting\MeetingStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('meetings')
            ->whereNull('status')
            ->orWhere('status', '')
            ->update(['status' => MeetingStatus::DRAFT->value]);

        Schema::table('meetings', function (Blueprint $table) {
            $table->string('status')->default(MeetingStatus::DRAFT->value)->change();
        });
    }

    public function down(): void
    {
        Schema::table('meetings', function (Blueprint $table) {
            $table->string('status')->default(null)->change();
        });
    }
};
