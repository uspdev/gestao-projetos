<?php

namespace App\Policies;

use App\Models\Media;
use App\Models\Meeting;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class MediaPolicy
{
    public function view(User $user, Media $media): bool
    {
        $owner = $this->activeOwner($media);

        if (! $owner) {
            return false;
        }

        return $user->isAdmin() || $this->relatedProjects($owner)
            ->contains(fn (Project $project) => $user->isViewerOfProject($project));
    }

    public function viewOriginal(User $user, Media $media): bool
    {
        $owner = $this->activeOwner($media);

        if (! $owner) {
            return false;
        }

        return $user->isAdmin()
            || (int) $media->uploaded_by === (int) $user->id && $this->view($user, $media)
            || $this->relatedProjects($owner)
                ->contains(fn (Project $project) => $user->isAdminOfProject($project));
    }

    public function create(User $user, Model $owner): bool
    {
        if (! $this->isActiveOwner($owner) || $this->isLockedTask($owner)) {
            return false;
        }

        return $user->isAdmin() || $this->relatedProjects($owner)
            ->contains(fn (Project $project) => $user->isContributorOfProject($project));
    }

    public function update(User $user, Media $media): bool
    {
        return $this->canManage($user, $media);
    }

    public function delete(User $user, Media $media): bool
    {
        return $this->canManage($user, $media);
    }

    private function canManage(User $user, Media $media): bool
    {
        $owner = $this->activeOwner($media);

        if (! $owner || $this->isLockedTask($owner)) {
            return false;
        }

        return $user->isAdmin()
            || ((int) $media->uploaded_by === (int) $user->id && $this->view($user, $media))
            || $this->relatedProjects($owner)
                ->contains(fn (Project $project) => $user->isAdminOfProject($project));
    }

    private function activeOwner(Media $media): ?Model
    {
        $owner = $media->model;

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
