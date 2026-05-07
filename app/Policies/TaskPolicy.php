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
        return $user->isViewerOfProject($project);
    }

    public function view(User $user, Task $task): bool
    {
        return $user->isViewerOfProject($task->project);
    }

    public function create(User $user, Project $project): bool
    {
        return $user->isContributorOfProject($project);
    }

    public function update(User $user, Task $task): bool
    {
        return $user->isAdminOfProject($task->project) || $user->isTaskAssignee($task);
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->isAdminOfProject($task->project) || $user->isTaskCreator($task);
    }

    public function storeAssignee(User $user, Task $task): bool
    {
        return $user->isAdminOfProject($task->project) || $user->isTaskCreator($task);
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
