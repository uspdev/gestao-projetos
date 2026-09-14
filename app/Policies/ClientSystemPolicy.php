<?php

namespace App\Policies;

use App\Models\ClientSystem;
use App\Models\Project;
use App\Models\User;

class ClientSystemPolicy
{
    public function create(User $user, Project $project): bool
    {
        return ! $project->trashed()
            && ($user->isAdmin() || $user->isAdminOfProject($project));
    }

    public function update(User $user, ClientSystem $clientSystem): bool
    {
        $project = $clientSystem->project;

        return $project !== null
            && ($user->isAdmin() || $user->isAdminOfProject($project));
    }

    public function manageApiKeys(User $user, ClientSystem $clientSystem): bool
    {
        return $this->update($user, $clientSystem);
    }
}
