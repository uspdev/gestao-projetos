<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

class TaskPolicy
{
    public function before(User $user, $ability): ?bool
    {
        // if ($user->isAdmin()) {
        //     return true;
        // }

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
        //   return true;
        if ($task->isLocked()) {
            return false;
        }
        return $user->isAdminOfProject($task->project) || $user->isTaskAssignee($task);
    }

    public function delete(User $user, Task $task): bool
    {
        if ($task->isLocked()) {
            return false;
        }
        return $user->isAdminOfProject($task->project)
            || $user->isTaskCreator($task);
    }

    public function storeAssignee(User $user, Task $task): bool
    {
        if ($task->isLocked()) {
            return false;
        }
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

    /**
     * Permite atualizar o status mesmo que uma tarefa esteja concluída
     */
    public function UpdateStatus(User $user, Task $task): bool
    {
        if ($task->isLocked()) {
            return true;
        }
        return false;
    }
}
