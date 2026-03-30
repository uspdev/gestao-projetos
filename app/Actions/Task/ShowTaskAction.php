<?php

namespace App\Actions\Task;

use App\Models\Task;

class ShowTaskAction
{
    public function execute(Task $task): Task
    {
        return $task->load([
            'project:id,name,status',
            'users:id,name,email',
        ]);
    }
}