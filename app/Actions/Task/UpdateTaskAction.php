<?php

namespace App\Actions\Task;

use App\Models\Task;
use Illuminate\Support\Facades\DB;

class UpdateTaskAction
{
    public function execute(Task $task, array $data, int $userId): Task
    {
        return DB::transaction(function () use ($task, $data, $userId) {
            $data['updated_by'] = $userId;

            $task->update($data);

            return $task->fresh();
        });
    }
}