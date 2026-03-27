<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{   

    public function before(User $user, $ability): ?bool
    {
        if($user->hasRole('admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user): bool
    {   
        return true;
    }

    public function view(User $user, Project $project): bool
    {
        return $user->isMemberOfProject($project);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Project $project): bool
    {
        return $user->isMemberOfProject($project);
    }

    public function delete(User $user, Project $project): bool
    {
        return false;
    }

    public function restore(User $user, Project $project): bool
    {
        return false;
    }

    public function forceDelete(User $user, Project $project): bool
    {
        return false;
    }
}
