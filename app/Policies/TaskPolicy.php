<?php

namespace App\Policies;

use App\Models\Module;
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
        return $this->tasksModuleEnabled($project) && $user->isViewerOfProject($project);
    }

    public function view(User $user, Task $task): bool
    {
        return $this->tasksModuleEnabled($task->project) && $user->isViewerOfProject($task->project);
    }

    public function create(User $user, Project $project): bool
    {
        return $this->tasksModuleEnabled($project) && $user->isContributorOfProject($project);
    }

    public function update(User $user, Task $task): bool
    {
        //   return true;
        if (! $this->tasksModuleEnabled($task->project)) {
            return false;
        }

        if ($task->isLocked()) {
            return false;
        }
        return $user->isAdminOfProject($task->project) || $user->isTaskAssignee($task);
    }

    public function delete(User $user, Task $task): bool
    {
        if (! $this->tasksModuleEnabled($task->project)) {
            return false;
        }

        if ($task->isLocked()) {
            return false;
        }
        return $user->isAdminOfProject($task->project)
            || $user->isTaskCreator($task);
    }

    public function storeAssignee(User $user, Task $task): bool
    {
        if (! $this->tasksModuleEnabled($task->project)) {
            return false;
        }

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
        if (! $this->tasksModuleEnabled($task->project)) {
            return false;
        }

        if ($task->isLocked()) {
            return true;
        }
        return false;
    }

    private function tasksModuleEnabled(Project $project): bool
    {
        return Module::isEnabledForProject($project, 'tasks');
    }
}
