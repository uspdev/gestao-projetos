<?php

namespace App\Services;

use App\Morphs\CommentableMap;
use App\Models\Media;
use App\Models\Meeting;
use App\Models\MeetingItem;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Support\Files\FileReferenceContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

class FileReferenceSelector
{
    /** @return array{results: Collection<int, array{uuid: string, name: string}>, shareable_results?: Collection<int, array{uuid: string, name: string}>} */
    public function select(User $user, FileReferenceContext $context): array
    {
        return match ($context->type) {
            'project' => $this->forProject($user, Project::query()->findOrFail($context->id)),
            'task' => $this->forTask($user, Task::query()->findOrFail($context->id)),
            'meeting' => $this->forMeeting($user, Meeting::query()->findOrFail($context->id)),
            'meeting_item' => $this->forMeetingItem($user, MeetingItem::query()->findOrFail($context->id)),
            'comment' => $this->forComment($user, $context),
        };
    }

    /** @return array{results: Collection<int, array{uuid: string, name: string}>} */
    private function forProject(User $user, Project $project): array
    {
        Gate::forUser($user)->authorize('view', $project);

        return $this->payload($user, $project->media()->latest()->get());
    }

    /** @return array{results: Collection<int, array{uuid: string, name: string}>} */
    private function forTask(User $user, Task $task): array
    {
        Gate::forUser($user)->authorize('view', $task);

        return $this->payload(
            $user,
            $task->media()
                ->latest()
                ->get()
                ->merge($task->project->media()->latest()->get())
                ->unique('id'),
        );
    }

    /** @return array{results: Collection<int, array{uuid: string, name: string}>, shareable_results: Collection<int, array{uuid: string, name: string}>, shareable_groups: Collection<int, array{label: string, results: Collection<int, array{uuid: string, name: string}>}>} */
    private function forMeeting(User $user, Meeting $meeting): array
    {
        abort_unless(! $meeting->trashed() && $this->canViewMeeting($user, $meeting), 404);

        $shareableGroups = Gate::forUser($user)->allows('manageFileShares', $meeting)
            ? $this->visibleShareableGroups($user, $this->shareableMeetingSources($meeting))
            : collect();

        $payload = $this->payload(
            $user,
            $meeting->media()
                ->latest()
                ->get()
                ->merge($meeting->sharedFiles()->latest()->get())
                ->unique('id'),
        );

        $payload['shareable_groups'] = $shareableGroups;
        $payload['shareable_results'] = $shareableGroups
            ->pluck('results')
            ->collapse()
            ->values();

        return $payload;
    }

    /** @return array{results: Collection<int, array{uuid: string, name: string}>, shareable_results: Collection<int, array{uuid: string, name: string}>} */
    private function forMeetingItem(User $user, MeetingItem $item): array
    {
        $meeting = $item->meeting;
        abort_unless($meeting instanceof Meeting, 404);

        return $this->forMeeting($user, $meeting);
    }

    /** @return array{results: Collection<int, array{uuid: string, name: string}>}|array{results: Collection<int, array{uuid: string, name: string}>, shareable_results: Collection<int, array{uuid: string, name: string}>} */
    private function forComment(User $user, FileReferenceContext $context): array
    {
        $commentableClass = CommentableMap::resolveClass((string) $context->commentableType);
        abort_unless($commentableClass, 404);

        $commentable = $commentableClass::query()->findOrFail($context->commentableId);

        return match (true) {
            $commentable instanceof Project => $this->forProject($user, $commentable),
            $commentable instanceof Task => $this->forTask($user, $commentable),
            $commentable instanceof Meeting => $this->forMeeting($user, $commentable),
            default => abort(404),
        };
    }

    private function canViewMeeting(User $user, Meeting $meeting): bool
    {
        return $meeting->projects()
            ->get()
            ->contains(fn (Project $project) => Gate::forUser($user)->allows('view', [$meeting, $project]));
    }

    /** @return Collection<int, array{label: string, media: Collection<int, Media>}> */
    private function shareableMeetingSources(Meeting $meeting): Collection
    {
        $sharedMediaIds = $meeting->sharedFiles()->pluck('media.id');
        $linkedProjects = $meeting->projects()->get();
        $agendaOwners = $meeting->meetingItems()
            ->with('discussable')
            ->get()
            ->pluck('discussable');

        return $linkedProjects
            ->map(fn (Project $project) => [
                'label' => "Projeto vinculado: {$project->name}",
                'media' => $project->media()->latest()->get(),
            ])
            ->concat(
                $agendaOwners
                    ->filter(fn (mixed $owner) => $owner instanceof Project)
                    ->reject(fn (Project $project) => $linkedProjects->contains('id', $project->id))
                    ->unique('id')
                    ->map(fn (Project $project) => [
                        'label' => "Projeto na pauta: {$project->name}",
                        'media' => $project->media()->latest()->get(),
                    ])
            )
            ->concat(
                $agendaOwners
                    ->filter(fn (mixed $owner) => $owner instanceof Task)
                    ->unique('id')
                    ->map(fn (Task $task) => [
                        'label' => "Tarefa na pauta: {$task->title}",
                        'media' => $task->media()->latest()->get(),
                    ])
            )
            ->map(fn (array $source) => [
                ...$source,
                'media' => $source['media']
                    ->reject(fn (Media $media) => $sharedMediaIds->contains($media->id))
                    ->values(),
            ])
            ->filter(fn (array $source) => $source['media']->isNotEmpty())
            ->values();
    }

    /** @param Collection<int, array{label: string, media: Collection<int, Media>}> $sources
     *  @return Collection<int, array{label: string, results: Collection<int, array{uuid: string, name: string}>}>
     */
    private function visibleShareableGroups(User $user, Collection $sources): Collection
    {
        return $sources
            ->map(fn (array $source) => [
                'label' => $source['label'],
                'results' => $this->visibleFiles($user, $source['media']),
            ])
            ->filter(fn (array $group) => $group['results']->isNotEmpty())
            ->values();
    }

    /**
     * @param  Collection<int, Media>  $media
     * @param  Collection<int, Media>|null  $shareableMedia
     * @return array{results: Collection<int, array{uuid: string, name: string}>, shareable_results?: Collection<int, array{uuid: string, name: string}>}
     */
    private function payload(User $user, Collection $media, ?Collection $shareableMedia = null): array
    {
        $payload = ['results' => $this->visibleFiles($user, $media)];

        if ($shareableMedia !== null) {
            $payload['shareable_results'] = $this->visibleFiles($user, $shareableMedia);
        }

        return $payload;
    }

    /** @param Collection<int, Media> $media */
    private function visibleFiles(User $user, Collection $media): Collection
    {
        return $media
            ->filter(fn (Media $media) => Gate::forUser($user)->allows('view', $media))
            ->map(fn (Media $media) => [
                'uuid' => $media->uuid,
                'name' => $media->display_name,
            ])
            ->values();
    }
}
