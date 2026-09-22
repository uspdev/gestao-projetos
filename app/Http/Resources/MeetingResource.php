<?php

namespace App\Http\Resources;

use App\Models\Comment;
use App\Models\Meeting;
use App\Models\MeetingItem;
use App\Models\Project;
use App\Models\Task;
use App\Morphs\DiscussableMap;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MeetingResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Meeting $meeting */
        $meeting = $this->resource;
        $data = [
            'id' => $meeting->id,
            'title' => $meeting->title,
            'status' => [
                'value' => $meeting->status->value,
                'label' => $meeting->status->label(),
            ],
            'scheduled_at' => $meeting->scheduled_at?->toISOString(),
            'location' => $meeting->location,
            'projects' => $meeting->projects
                ->map(fn (Project $project): array => [
                    'id' => $project->id,
                    'slug' => $project->slug,
                    'name' => $project->name,
                ])
                ->values()
                ->all(),
            'created_at' => $meeting->created_at?->toISOString(),
            'updated_at' => $meeting->updated_at?->toISOString(),
            'web_url' => route('projects.meetings.show', [$request->route('project'), $meeting]),
        ];

        if (! $meeting->relationLoaded('meetingItems')) {
            return $data;
        }

        return array_merge($data, [
            'notes' => $meeting->notes,
            'ata' => $meeting->ata,
            'transcription' => $meeting->transcription,
            'agenda' => $meeting->meetingItems
                ->map(fn (MeetingItem $item): array => $this->agendaItem($item))
                ->values()
                ->all(),
            'comments' => $meeting->comments
                ->map(fn (Comment $comment): array => [
                    'text' => $comment->text,
                    'created_at' => $comment->created_at?->toISOString(),
                    'author' => ['name' => $comment->user?->name],
                ])
                ->values()
                ->all(),
        ]);
    }

    /** @return array<string, mixed> */
    private function agendaItem(MeetingItem $item): array
    {
        $reference = $item->discussable;

        return [
            'type' => match (DiscussableMap::resolveClass((string) $item->discussable_type)) {
                Project::class => 'project',
                Task::class => 'task',
                default => 'independent',
            },
            'title' => $item->title ?? $reference?->title ?? $reference?->name,
            'notes' => $item->notes,
            'reference' => match (true) {
                $reference instanceof Project => [
                    'id' => $reference->id,
                    'slug' => $reference->slug,
                    'name' => $reference->name,
                ],
                $reference instanceof Task => [
                    'id' => $reference->id,
                    'title' => $reference->title,
                ],
                default => null,
            },
        ];
    }
}
