<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function before(User $user, $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    public function viewAny(User $user, Project $project): bool
    {
        return $user->isMemberOfProject($project);
    }

    public function view(User $user, Task $task): bool
    {
        return $user->isMemberOfProject($task->project);
    }

    public function create(User $user, Project $project): bool
    {
        return $user->isMemberOfProject($project);
    }

    public function update(User $user, Task $task): bool
    {
        return $user->isOwnerOfProject($task->project) || $user->isTaskAssignee($task);
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->isOwnerOfProject($task->project) || $user->isTaskCreator($task);
    }

    public function storeAssignee(User $user, Task $task): bool
    {
        return $user->isOwnerOfProject($task->project) || $user->isTaskCreator($task);
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
