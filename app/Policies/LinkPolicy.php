<?php

namespace App\Policies;

use App\Models\Link;
use App\Models\Meeting;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class LinkPolicy
{
    public function view(User $user, Link $link): bool
    {
        $owner = $this->activeOwner($link);

        if (! $owner) {
            return false;
        }

        return $user->isAdmin()
            || $this->relatedProjects($owner)
                ->contains(fn (Project $project) => $user->isViewerOfProject($project))
            || $this->isSharedWithViewableMeeting($user, $link);
    }

    public function create(User $user, Model $owner): bool
    {
        if (! $this->isActiveOwner($owner) || $this->isLockedTask($owner)) {
            return false;
        }

        return $user->isAdmin() || $this->relatedProjects($owner)
            ->contains(fn (Project $project) => $user->isContributorOfProject($project));
    }

    public function update(User $user, Link $link): bool
    {
        return $this->canManage($user, $link);
    }

    public function delete(User $user, Link $link): bool
    {
        return $this->canManage($user, $link);
    }

    private function canManage(User $user, Link $link): bool
    {
        $owner = $this->activeOwner($link);

        if (! $owner || $this->isLockedTask($owner)) {
            return false;
        }

        return $user->isAdmin()
            || ((int) $link->created_by === (int) $user->id && $this->view($user, $link))
            || $this->relatedProjects($owner)
                ->contains(fn (Project $project) => $user->isAdminOfProject($project));
    }

    private function activeOwner(Link $link): ?Model
    {
        $owner = $link->linkable;

        return $owner instanceof Model && $this->isActiveOwner($owner) ? $owner : null;
    }

    private function isActiveOwner(Model $owner): bool
    {
        return in_array($owner::class, [Project::class, Task::class, Meeting::class], true)
            && (! method_exists($owner, 'trashed') || ! $owner->trashed());
    }

    private function isLockedTask(Model $owner): bool
    {
        return $owner instanceof Task && $owner->isLocked();
    }

    private function isSharedWithViewableMeeting(User $user, Link $link): bool
    {
        return $link->sharedWithMeetings()
            ->with('projects')
            ->get()
            ->contains(function (Meeting $meeting) use ($user): bool {
                if ($meeting->trashed()) {
                    return false;
                }

                return $meeting->projects->contains(
                    fn (Project $project) => Gate::forUser($user)->allows('view', [$meeting, $project])
                );
            });
    }

    /** @return Collection<int, Project> */
    private function relatedProjects(Model $owner): Collection
    {
        return (match (true) {
            $owner instanceof Project => collect([$owner]),
            $owner instanceof Task => collect([$owner->project]),
            $owner instanceof Meeting => $owner->projects,
            default => collect(),
        })->filter(fn (mixed $project) => $project instanceof Project && ! $project->trashed());
    }
}
