<?php

namespace App\Policies;

use App\Models\Meeting;
use App\Models\Module;
use App\Models\Project;
use App\Models\User;

class MeetingPolicy
{
    public function viewAny(User $user, Project $project): bool
    {
        return $this->meetingsModuleEnabled($project)
            && $user->isViewerOfProject($project);
    }

    public function view(User $user, Meeting $meeting, Project $project): bool
    {
        if (!$this->meetingBelongsToProject($meeting, $project)) {
            return false;
        }

        return $this->meetingsModuleEnabled($project)
            && $user->isViewerOfProject($project);
    }

    public function create(User $user, Project $project): bool
    {
        return $this->meetingsModuleEnabled($project)
            && $user->isContributorOfProject($project);
    }

    public function update(User $user, Meeting $meeting, Project $project): bool
    {
        if (!$this->meetingBelongsToProject($meeting, $project)) {
            return false;
        }

        return $this->meetingsModuleEnabled($project)
            && $user->isContributorOfProject($project);
    }

    public function delete(User $user, Meeting $meeting, Project $project): bool
    {
        if (!$this->meetingBelongsToProject($meeting, $project)) {
            return false;
        }

        return $this->meetingsModuleEnabled($project)
            && $user->isContributorOfProject($project);
    }

    public function restore(User $user, Meeting $meeting): bool
    {
        return false;
    }

    public function forceDelete(User $user, Meeting $meeting): bool
    {
        return false;
    }

    private function meetingsModuleEnabled(Project $project): bool
    {
        return Module::isEnabledForProject($project, 'meetings');
    }

    //checagem possivelmente redundante, mas acho melhor manter por enquanto.
    private function meetingBelongsToProject(Meeting $meeting, Project $project): bool
    {
        return $meeting->projects()
            ->where('projects.id', $project->id)
            ->exists();
    }
}
