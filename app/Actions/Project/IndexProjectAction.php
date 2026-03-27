<?php

namespace App\Actions\Project;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class IndexProjectAction
{
    public function execute(User $user): Collection
    {
        return Project::accessibleBy($user)
                       ->with([
                            'users:id,name', 
                            'tasks:id,project_id,status' 
                        ])
                       ->latest()
                       ->get();
    }
}
