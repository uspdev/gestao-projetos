<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function before(User $user, $ability): ?bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user, Project $project): bool
    {
        return $user->isMemberOfProject($project);
    }

    public function view(User $user, Project $project): bool
    {
        return $user->isMemberOfProject($project);
    }

    public function create(User $user, Project $project): bool
    {
        return $user->isMemberOfProject($project);
    }

    public function update(User $user, Task $task): bool
    {   
        if (!$user->isMemberOfProject($task->project)) {
            return false;
        }

        return $task->users->contains($user->id) || $task->created_by === $user->id;
    }

    public function delete(User $user, Task $task): bool
    {   
        if (!$user->isMemberOfProject($task->project)) {
            return false;
        }

        return $task->users->contains($user->id) || $task->created_by === $user->id;
    }

    public function restore(User $user, Task $task): bool
    {
        return false;
    }

    public function forceDelete(User $user, Task $task): bool
    {
        return false;
    }
}
