<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('tasks')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('task_user')
                    ->whereColumn('task_user.task_id', 'tasks.id');
            })
            ->update([
                'status' => 'NEW',
            ]);

        DB::table('tasks')
            ->where('status', 'TO_DO')
            ->update([
                'status' => 'ASSIGNED',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
