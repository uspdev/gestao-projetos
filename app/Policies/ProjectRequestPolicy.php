<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\ProjectRequest;
use App\Models\User;

class ProjectRequestPolicy
{
    public function viewAny(User $user, Project $project): bool
    {
        return $user->isContributorOfProject($project);
    }

    public function view(User $user, ProjectRequest $projectRequest): bool
    {
        return $projectRequest->project !== null
            && $user->isContributorOfProject($projectRequest->project);
    }
}
