<?php

namespace App\Actions\Task;

use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Support\Facades\DB;

class CreateTaskAction
{
    public function execute(array $data, int $userId): Task
    {
        return DB::transaction(function () use ($data, $userId) {
            $data['created_by'] = $userId;
            $data['status'] = $data['status'] ?? TaskStatus::TO_DO->value;

            $task = Task::create($data);
            $task->users()->attach($userId);

            return $task;
        });
    }
}