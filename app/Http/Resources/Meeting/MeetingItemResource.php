<?php

namespace App\Http\Resources\Meeting;

use App\Http\Resources\Project\ProjectSummaryResource;
use App\Models\MeetingItem;
use App\Models\Project;
use App\Models\Task;
use App\Morphs\DiscussableMap;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MeetingItemResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var MeetingItem $item */
        $item = $this->resource;
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
                    ...(new ProjectSummaryResource($reference))->resolve($request),
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
