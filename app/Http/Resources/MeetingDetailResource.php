<?php

namespace App\Http\Resources;

use App\Models\MeetingItem;
use App\Models\Project;
use App\Models\Task;
use App\Morphs\DiscussableMap;
use Illuminate\Http\Request;

class MeetingDetailResource extends ApiDetailResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        $meeting = $this->resource;

        return array_merge((new MeetingResource($meeting))->resolve($request), [
            'notes' => $meeting->notes,
            'ata' => $meeting->ata,
            'transcription' => $meeting->transcription,
            'agenda' => $meeting->meetingItems
                ->map(fn (MeetingItem $item): array => $this->agendaItem($item))
                ->values()
                ->all(),
        ], $this->visibleDetail($request));
    }

    /** @return array<string, mixed> */
    private function agendaItem(MeetingItem $item): array
    {
        $reference = $item->discussable;

        return [
            'id' => $item->id,
            'position' => $item->order,
            'type' => match (DiscussableMap::resolveClass((string) $item->discussable_type)) {
                Project::class => 'project',
                Task::class => 'task',
                default => 'independent',
            },
            'title' => $item->title ?? $reference?->title ?? $reference?->name,
            'notes' => $item->notes,
            'reference' => match (true) {
                $reference instanceof Project => [
                    'type' => 'project',
                    'id' => $reference->id,
                    'slug' => $reference->slug,
                    'name' => $reference->name,
                    'web_url' => route('projects.show', $reference),
                ],
                $reference instanceof Task => [
                    'type' => 'task',
                    'id' => $reference->id,
                    'title' => $reference->title,
                    'web_url' => route('tasks.show', $reference),
                ],
                default => null,
            },
        ];
    }
}
