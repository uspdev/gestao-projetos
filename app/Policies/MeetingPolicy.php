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

    public function duplicate(User $user, Meeting $meeting, Project $project): bool
    {
        return $this->meetingBelongsToProject($meeting, $project)
            && $this->create($user, $project);
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

    public function comment(User $user, Meeting $meeting): bool
    {
        return $meeting->projects->some(
            fn(Project $project) => $user->isContributorOfProject($project)
        );
    }

    public function manageFileShares(User $user, Meeting $meeting): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $meeting->projects()
            ->get()
            ->contains(fn (Project $project) => $this->meetingsModuleEnabled($project)
                && $user->isContributorOfProject($project));
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

    // Verifica se a reunião pertence ao projeto ou, no caso de subprojetos, se pertence ao projeto pai
    private function meetingBelongsToProject(Meeting $meeting, Project $project): bool
    {
        if ($meeting->projects()
            ->where('projects.id', $project->id)
            ->exists()
        ) {
            return true;
        }

        if (! $project->isSubproject() || ! $project->parent) {
            return false;
        }

        return $meeting->projects()
            ->where('projects.id', $project->parent->id)
            ->exists();
    }
}
