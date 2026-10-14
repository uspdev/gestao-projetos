<?php

namespace App\Services\Api;

use App\Models\Comment;
use App\Models\Meeting;
use App\Models\MeetingItem;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ProjectIncomingMentions
{
    /** @return array{locations_count: int, sources_count: int, sources: list<array<string, mixed>>} */
    public function for(Model $target, Project $project): array
    {
        $empty = [
            'locations_count' => 0,
            'sources_count' => 0,
            'sources' => [],
        ];

        if (! Schema::hasTable('mentions') || ! method_exists($target, 'incomingMentions')) {
            return $empty;
        }

        $entries = $target->incomingMentions()
            ->with('source')
            ->get()
            ->sortByDesc(fn ($mention): int => $mention->source?->updated_at?->getTimestamp() ?? 0)
            ->map(fn ($mention): ?array => $this->entry($mention->source, (string) $mention->source_field, $project))
            ->filter()
            ->values();

        $sources = $entries
            ->groupBy('group_key')
            ->map(function ($group): array {
                $first = $group->first();

                return [
                    'source' => $first['source'],
                    'locations' => $group
                        ->pluck('location')
                        ->unique(fn (array $location): string => implode(':', [
                            $location['source_type'],
                            $location['source_id'],
                            $location['field'],
                        ]))
                        ->values()
                        ->all(),
                ];
            })
            ->values();

        return [
            'locations_count' => $sources->sum(fn (array $source): int => count($source['locations'])),
            'sources_count' => $sources->count(),
            'sources' => $sources->all(),
        ];
    }

    /** @return array<string, mixed>|null */
    private function entry(?Model $source, string $field, Project $project): ?array
    {
        return match (true) {
            $source instanceof Project => $this->projectEntry($source, $field, $project),
            $source instanceof Task => $this->taskEntry($source, $field, $project),
            $source instanceof Meeting => $this->meetingEntry($source, $field, $project),
            $source instanceof MeetingItem => $this->meetingItemEntry($source, $field, $project),
            $source instanceof Comment => $this->commentEntry($source, $field, $project),
            default => null,
        };
    }

    /** @return array<string, mixed>|null */
    private function projectEntry(Project $source, string $field, Project $project): ?array
    {
        if ((int) $source->getKey() !== (int) $project->getKey()) {
            return null;
        }

        $url = route('projects.show', $source).'#project-description-'.$source->getKey();

        return $this->makeEntry($source, 'project', $source->name, $field, 'Descrição', $url, $project);
    }

    /** @return array<string, mixed>|null */
    private function taskEntry(Task $source, string $field, Project $project): ?array
    {
        if (! $project->isModuleEnabled('tasks') || (int) $source->project_id !== (int) $project->getKey()) {
            return null;
        }

        $url = route('tasks.show', $source).'#task-description-'.$source->getKey();

        return $this->makeEntry($source, 'task', $source->title, $field, 'Descrição', $url, $project);
    }

    /** @return array<string, mixed>|null */
    private function meetingEntry(Meeting $source, string $field, Project $project): ?array
    {
        if (! $this->meetingIsVisible($source, $project)) {
            return null;
        }

        $label = match ($field) {
            'ata' => 'Ata',
            'transcription' => 'Transcrição',
            default => 'Anotações prévias',
        };
        $url = route('projects.meetings.show', [$project, $source]).'#meeting-notes-'.$source->getKey();

        return $this->makeEntry($source, 'meeting', $source->title, $field, $label, $url, $project);
    }

    /** @return array<string, mixed>|null */
    private function meetingItemEntry(MeetingItem $source, string $field, Project $project): ?array
    {
        $source->loadMissing(['meeting', 'discussable']);
        $meeting = $source->meeting;

        if (! $meeting || ! $this->meetingIsVisible($meeting, $project)) {
            return null;
        }

        $url = deep_link('projects.meetings.show', [$project, $meeting], target: $source);

        return $this->makeEntry(
            $meeting,
            'meeting',
            $meeting->title,
            $field,
            'Anotações prévias do item',
            $url,
            $project,
            $source,
        );
    }

    /** @return array<string, mixed>|null */
    private function commentEntry(Comment $source, string $field, Project $project): ?array
    {
        if (! $source->is_active) {
            return null;
        }

        $source->loadMissing('commentable');
        $commentable = $source->commentable;

        return match (true) {
            $commentable instanceof Project && (int) $commentable->getKey() === (int) $project->getKey() =>
                $this->makeEntry(
                    $commentable,
                    'project',
                    $commentable->name,
                    $field,
                    'Comentário neste projeto',
                    deep_link('projects.show', $commentable, target: $source),
                    $project,
                    $source,
                ),
            $commentable instanceof Task
                && $project->isModuleEnabled('tasks')
                && (int) $commentable->project_id === (int) $project->getKey() =>
                $this->makeEntry(
                    $commentable,
                    'task',
                    $commentable->title,
                    $field,
                    'Comentário nesta tarefa',
                    deep_link('tasks.show', $commentable, target: $source),
                    $project,
                    $source,
                ),
            $commentable instanceof Meeting && $this->meetingIsVisible($commentable, $project) =>
                $this->makeEntry(
                    $commentable,
                    'meeting',
                    $commentable->title,
                    $field,
                    'Comentário nesta reunião',
                    deep_link('projects.meetings.show', [$project, $commentable], target: $source),
                    $project,
                    $source,
                ),
            default => null,
        };
    }

    private function meetingIsVisible(Meeting $meeting, Project $project): bool
    {
        return $project->isModuleEnabled('meetings')
            && $meeting->projects()->whereKey($project->getKey())->exists();
    }

    /** @return array<string, mixed> */
    private function makeEntry(
        Model $group,
        string $groupType,
        string $title,
        string $field,
        string $label,
        string $url,
        Project $project,
        ?Model $locationSource = null,
    ): array {
        $locationSource ??= $group;

        return [
            'group_key' => $groupType.':'.$group->getKey(),
            'source' => [
                'type' => $groupType,
                'id' => $group->getKey(),
                'title' => $title,
                'web_url' => match ($groupType) {
                    'project' => route('projects.show', $group),
                    'task' => route('tasks.show', $group),
                    'meeting' => route('projects.meetings.show', [$project, $group]),
                },
            ],
            'location' => [
                'source_type' => $locationSource->getMorphClass(),
                'source_id' => $locationSource->getKey(),
                'field' => $field,
                'label' => $label,
                'web_url' => $url,
            ],
        ];
    }
}
