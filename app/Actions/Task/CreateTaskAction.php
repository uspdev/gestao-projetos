<?php

namespace App\Actions\Task;

use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\Project; 
use Illuminate\Support\Facades\DB;

class CreateTaskAction
{
    public function execute(Project $project, array $data, int $userId): Task
    {
        return DB::transaction(function () use ($project, $data, $userId) {
            
            $data['project_id'] = $project->id;
            
            $data['created_by'] = $userId;
            $data['status'] = $data['status'] ?? TaskStatus::TO_DO->value;

            $task = Task::create($data);
            $task->users()->attach($userId);

            return $task;
        });
    }
}