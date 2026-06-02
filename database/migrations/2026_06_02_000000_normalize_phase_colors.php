<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to normalize the `color` column of the `phases` table.
 * It removes any leading "badge-" prefix from stored values, leaving
 * only the raw color name (e.g. "primary", "success").
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('phases')) {
            return;
        }

        // Update each row, stripping the "badge-" prefix if present.
        DB::table('phases')->where('color', 'like', 'badge-%')->update([
            'color' => DB::raw("TRIM(LEADING 'badge-' FROM color)")
        ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('phases')) {
            return;
        }

        // Re‑add the prefix for rollback.
        DB::table('phases')->where('color', 'not like', 'badge-%')->update([
            'color' => DB::raw("CONCAT('badge-', color)")
        ]);
    }
};
