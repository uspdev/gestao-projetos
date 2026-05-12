<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('projects')
            ->where('status', 'PLANNING')
            ->update([
                'status' => 'PLANNED',
                'phase' => 'PLANNING',
            ]);

        DB::table('projects')
            ->where('status', 'DEVELOPMENT')
            ->update([
                'status' => 'ACTIVE',
                'phase' => 'DEVELOPMENT',
            ]);

        DB::table('projects')
            ->where('status', 'PRODUCTION')
            ->update([
                'status' => 'ACTIVE',
                'phase' => 'PRODUCTION',
            ]);

        DB::table('projects')
            ->where('status', 'DEACTIVATED')
            ->update([
                'status' => 'ARCHIVED',
                'phase' => 'RETIRED',
            ]);

        DB::table('projects')
            ->where('status', 'MIGRATED')
            ->update([
                'status' => 'ARCHIVED',
                'phase' => 'RETIRED',
            ]);
    }

    public function down(): void
    {
        // No safe automatic rollback for status/phase mapping.
    }
};
