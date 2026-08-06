<?php

namespace App\Services\Mentions;

use App\Models\Comment;
use App\Models\Meeting;
use App\Models\MeetingItem;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

final class MentionProjectContextResolver
{
    /** @return Collection<int, Project> */
    public function forProjectSearch(Model $source): Collection
    {
        return match (true) {
            $source instanceof Project => $this->relatedProjects($source),
            $source instanceof Task => $this->contextualProjects($source->loadMissing('project')->project),
            $source instanceof Meeting => $this->meetingContextProjects($source),
            $source instanceof MeetingItem => $this->meetingItemContextProjects($source),
            $source instanceof Comment => $this->projectCommentContext($source),
            default => collect(),
        };
    }

    /** @return Collection<int, Project> */
    public function forTaskSearch(Model $source): Collection
    {
        return match (true) {
            $source instanceof Project => $this->contextualProjects($source),
            $source instanceof Task => $this->contextualProjects($source->loadMissing('project')->project),
            $source instanceof Meeting => $this->meetingContextProjects($source),
            $source instanceof MeetingItem => $this->meetingItemContextProjects($source),
            $source instanceof Comment => $this->projectCommentContext($source),
            default => collect(),
        };
    }

    /** @return Collection<int, Task>|null */
    public function forTaskTargetSearch(Model $source): ?Collection
    {
        return match (true) {
            $source instanceof Meeting => $this->meetingAgendaTasks($source),
            $source instanceof MeetingItem => $this->meetingItemAgendaTasks($source),
            $source instanceof Comment => $this->commentTaskContext($source),
            default => null,
        };
    }

    /** @return Collection<int, Project> */
    public function forMeetingSearch(Model $source): Collection
    {
        return match (true) {
            $source instanceof Project => $this->contextualProjects($source),
            $source instanceof Task => $this->owningProject($source),
            $source instanceof Meeting => $this->meetingContextProjects($source),
            $source instanceof MeetingItem => $this->meetingItemMeetingContext($source),
            $source instanceof Comment => $this->meetingCommentContext($source),
            default => collect(),
        };
    }

    /** @return Collection<int, Project> */
    private function projectCommentContext(Comment $comment): Collection
    {
        $commentable = $this->commentable($comment);

        return match (true) {
            $commentable instanceof Project => $this->contextualProjects($commentable),
            $commentable instanceof Task => $this->contextualProjects(
                $commentable->loadMissing('project')->project
            ),
            $commentable instanceof Meeting => $this->meetingContextProjects($commentable),
            $commentable instanceof MeetingItem => $this->meetingItemContextProjects($commentable),
            default => collect(),
        };
    }

    /** @return Collection<int, Project> */
    private function meetingCommentContext(Comment $comment): Collection
    {
        $commentable = $this->commentable($comment);

        return match (true) {
            $commentable instanceof Project => $this->contextualProjects($commentable),
            $commentable instanceof Task => $this->owningProject($commentable),
            $commentable instanceof Meeting => $this->meetingContextProjects($commentable),
            $commentable instanceof MeetingItem => $this->meetingItemMeetingContext($commentable),
            default => collect(),
        };
    }

    private function commentable(Comment $comment): ?Model
    {
        $comment->loadMissing('commentable');

        return $comment->commentable;
    }

    /** @return Collection<int, Project> */
    private function contextualProjects(?Project $project): Collection
    {
        return $project
            ? collect([$project])->concat($this->relatedProjects($project))->values()
            : collect();
    }

    /** @return Collection<int, Project> */
    private function owningProject(Task $task): Collection
    {
        $project = $task->loadMissing('project')->project;

        return $project ? collect([$project]) : collect();
    }

    /** @return Collection<int, Project> */
    private function relatedProjects(Project $project): Collection
    {
        if (! Schema::hasColumn($project->getTable(), 'parent_id')) {
            return collect();
        }

        $project->loadMissing('parent');

        return collect([$project->parent])
            ->filter()
            ->merge($project->children()->get())
            ->values();
    }

    /** @return Collection<int, Project> */
    private function meetingContextProjects(Meeting $meeting): Collection
    {
        $meeting->loadMissing(['projects', 'meetingItems.discussable']);

        return $meeting->projects
            ->concat($meeting->meetingItems->map(
                fn (MeetingItem $item): ?Project => $this->projectForDiscussable($item->discussable)
            ))
            ->filter(fn (mixed $project): bool => $project instanceof Project)
            ->unique('id')
            ->values();
    }

    /** @return Collection<int, Project> */
    private function meetingItemContextProjects(MeetingItem $item): Collection
    {
        $item->loadMissing(['meeting.projects', 'meeting.meetingItems.discussable', 'discussable']);

        return ($item->meeting instanceof Meeting ? $this->meetingContextProjects($item->meeting) : collect())
            ->push($this->projectForDiscussable($item->discussable))
            ->filter(fn (mixed $project): bool => $project instanceof Project)
            ->unique('id')
            ->values();
    }

    /** @return Collection<int, Task> */
    private function meetingAgendaTasks(Meeting $meeting): Collection
    {
        $meeting->loadMissing('meetingItems.discussable');

        return $meeting->meetingItems
            ->map(fn (MeetingItem $item): ?Task => $item->discussable instanceof Task
                ? $item->discussable
                : null)
            ->filter(fn (mixed $task): bool => $task instanceof Task)
            ->unique('id')
            ->values();
    }

    /** @return Collection<int, Task> */
    private function meetingItemAgendaTasks(MeetingItem $item): Collection
    {
        $item->loadMissing('meeting.meetingItems.discussable');

        return $item->meeting instanceof Meeting
            ? $this->meetingAgendaTasks($item->meeting)
            : collect();
    }

    /** @return Collection<int, Project> */
    private function meetingItemMeetingContext(MeetingItem $item): Collection
    {
        $item->loadMissing('meeting');

        return $item->meeting instanceof Meeting
            ? $this->meetingContextProjects($item->meeting)
            : collect();
    }

    /** @return Collection<int, Task>|null */
    private function commentTaskContext(Comment $comment): ?Collection
    {
        $commentable = $this->commentable($comment);

        return match (true) {
            $commentable instanceof Meeting => $this->meetingAgendaTasks($commentable),
            $commentable instanceof MeetingItem => $this->meetingItemAgendaTasks($commentable),
            default => null,
        };
    }

    private function projectForDiscussable(?Model $discussable): ?Project
    {
        return match (true) {
            $discussable instanceof Project => $discussable,
            $discussable instanceof Task => $discussable->loadMissing('project')->project,
            default => null,
        };
    }
}
