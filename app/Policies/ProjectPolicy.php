<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

class ProjectPolicy
{

    public function before(User $user, $ability): ?bool
    {
        if ($user->isAdmin()) {
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
        return $user->isViewerOfProject($project);
    }

    public function create(User $user): bool
    {
        return $user->canAny([
            'senhaunica.estagiario',
            'senhaunica.docente',
            'senhaunica.servidor',
        ]);
    }

    public function update(User $user, Project $project): bool
    {
        return $user->isContributorOfProject($project);
    }

    public function updateModule(User $user, Project $project): bool
    {
        return $user->isAdminOfProject($project);
    }

    public function delete(User $user, Project $project): bool
    {
        return $user->isAdminOfProject($project);
    }

    public function comment(User $user, Project $project): bool
    {
        return $user->isContributorOfProject($project);
    }

    public function restore(User $user, Project $project): bool
    {
        return false;
    }

    public function forceDelete(User $user, Project $project): bool
    {
        return false;
    }

    public function storeMember(User $user, Project $project): bool
    {
        return $user->isAdminOfProject($project);
    }
}
