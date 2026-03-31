<?php

namespace App\Actions\Task;

use App\Models\Project;
use Illuminate\Database\Eloquent\Collection;

class IndexTaskAction
{
    public function execute(Project $project): Collection
    {
        return $project->tasks()
                       ->with('project:id,name,status')
                       ->with('users:id,name')
                       ->orderBy('priority', 'asc')
                       ->latest()
                       ->get();
    }
}