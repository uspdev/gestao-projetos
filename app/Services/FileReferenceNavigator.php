<?php

namespace App\Services;

use App\Models\Media;
use App\Models\Meeting;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

class FileReferenceNavigator
{
    private const FILES_PER_PAGE = 20;

    /**
     * Resolve a seção de Arquivos mais próxima do texto que contém a referência.
     *
     * @param  array{type?: string, id?: int, project_id?: int}  $context
     */
    public function url(User $user, Media $media, array $context = []): ?string
    {
        return $this->contextUrl($user, $media, $context)
            ?? $this->ownerUrl($user, $media)
            ?? $this->sharedMeetingUrl($user, $media);
    }

    /**
     * @param  array{type?: string, id?: int, project_id?: int}  $context
     */
    private function contextUrl(User $user, Media $media, array $context): ?string
    {
        $contextId = $context['id'] ?? null;

        if (! is_int($contextId)) {
            return null;
        }

        return match ($context['type'] ?? null) {
            'project' => $this->projectContextUrl($user, $media, $contextId),
            'task' => $this->taskContextUrl($user, $media, $contextId),
            'meeting' => $this->meetingContextUrl(
                $user,
                $media,
                $contextId,
                isset($context['project_id']) ? (int) $context['project_id'] : null,
            ),
            default => null,
        };
    }

    private function projectContextUrl(User $user, Media $media, int $projectId): ?string
    {
        $project = Project::query()->find($projectId);

        if (! $project
            || ! $this->isOwnedBy($media, $project)
            || ! Gate::forUser($user)->allows('view', $project)) {
            return null;
        }

        return $this->ownerSectionUrl('projects.show', $project, $media);
    }

    private function taskContextUrl(User $user, Media $media, int $taskId): ?string
    {
        $task = Task::query()->with('project')->find($taskId);

        if (! $task
            || ! $this->isOwnedBy($media, $task)
            || ! Gate::forUser($user)->allows('view', $task)) {
            return null;
        }

        return $this->ownerSectionUrl('tasks.show', $task, $media);
    }

    private function meetingContextUrl(
        User $user,
        Media $media,
        int $meetingId,
        ?int $preferredProjectId,
    ): ?string {
        $meeting = Meeting::query()
            ->with(['projects', 'sharedFiles'])
            ->find($meetingId);

        if (! $meeting
            || (! $this->isOwnedBy($media, $meeting)
                && ! $meeting->sharedFiles->contains('id', $media->id))) {
            return null;
        }

        $project = $this->viewableMeetingProject(
            $user,
            $meeting,
            $preferredProjectId,
        );

        if (! $project) {
            return null;
        }

        return $this->meetingSectionUrl($project, $meeting, $media);
    }

    private function ownerUrl(User $user, Media $media): ?string
    {
        $owner = $media->model;

        return match (true) {
            $owner instanceof Project
                && Gate::forUser($user)->allows('view', $owner)
                => $this->ownerSectionUrl('projects.show', $owner, $media),
            $owner instanceof Task
                && Gate::forUser($user)->allows('view', $owner)
                => $this->ownerSectionUrl('tasks.show', $owner, $media),
            $owner instanceof Meeting
                => $this->meetingOwnerUrl($user, $owner, $media),
            default => null,
        };
    }

    private function meetingOwnerUrl(User $user, Meeting $meeting, Media $media): ?string
    {
        $project = $this->viewableMeetingProject($user, $meeting);

        return $project ? $this->meetingSectionUrl($project, $meeting, $media) : null;
    }

    private function sharedMeetingUrl(User $user, Media $media): ?string
    {
        $meetings = $media->sharedWithMeetings()
            ->with('projects')
            ->orderByDesc('meetings.id')
            ->get();

        foreach ($meetings as $meeting) {
            $project = $this->viewableMeetingProject($user, $meeting);

            if ($project) {
                return $this->meetingSectionUrl($project, $meeting, $media);
            }
        }

        return null;
    }

    private function viewableMeetingProject(
        User $user,
        Meeting $meeting,
        ?int $preferredProjectId = null,
    ): ?Project {
        $meeting->loadMissing('projects');
        $projects = $meeting->projects;

        if ($preferredProjectId !== null) {
            $preferred = Project::query()->find($preferredProjectId);

            if ($preferred
                && Gate::forUser($user)->allows('view', [$meeting, $preferred])) {
                return $preferred;
            }
        }

        return $projects
            ->sortBy([['name', 'asc'], ['id', 'asc']])
            ->first(fn (Project $project) => Gate::forUser($user)
                ->allows('view', [$meeting, $project]));
    }

    private function ownerSectionUrl(string $route, Model $owner, Media $media): string
    {
        $page = $this->filePage($owner, $media);
        $url = route($route, $owner);

        if ($page > 1) {
            $url .= '?files_page='.$page;
        }

        return $url.'#file-'.$media->uuid;
    }

    private function meetingSectionUrl(Project $project, Meeting $meeting, Media $media): string
    {
        $url = route('projects.meetings.show', [$project, $meeting]);

        if ($this->isOwnedBy($media, $meeting)) {
            $page = $this->filePage($meeting, $media);

            if ($page > 1) {
                $url .= '?files_page='.$page;
            }
        }

        return $url.'#file-'.$media->uuid;
    }

    private function filePage(Model $owner, Media $media): int
    {
        $newerFiles = $owner->media()
            ->where(function ($query) use ($media): void {
                $query
                    ->where('created_at', '>', $media->created_at)
                    ->orWhere(function ($query) use ($media): void {
                        $query
                            ->where('created_at', $media->created_at)
                            ->where('id', '>', $media->id);
                    });
            })
            ->count();

        return intdiv($newerFiles, self::FILES_PER_PAGE) + 1;
    }

    private function isOwnedBy(Media $media, Model $owner): bool
    {
        return $media->model instanceof $owner
            && (int) $media->model_id === (int) $owner->getKey();
    }
}
