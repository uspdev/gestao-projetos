<?php

namespace App\Actions\Project;

use App\Models\Project;

class ShowProjectAction
{
    public function execute(Project $project): Project
    {
        return $project->load([
            'users:id,name,email',
            'tasks:id,project_id,title,status,due_date',
        ]);
    }
}
