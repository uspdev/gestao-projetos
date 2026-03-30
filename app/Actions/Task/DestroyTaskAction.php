<?php

namespace App\Actions\Task;

use App\Models\Task;
use Illuminate\Support\Facades\DB;

class DestroyTaskAction
{
    public function execute(Task $task): bool
    {
        return DB::transaction(function () use ($task) {
            return (bool) $task->delete();
        });
    }
}