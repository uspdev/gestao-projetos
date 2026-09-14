<?php

namespace App\Http\Resources;

use App\Models\Tag;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Task $task */
        $task = $this->resource;

        return [
            'id' => $task->id,
            'title' => $task->title,
            'description' => $task->description,
            'status' => [
                'value' => $task->status->value,
                'label' => $task->status->label(),
            ],
            'priority' => $task->priority === null ? null : [
                'value' => $task->priority->value,
                'label' => $task->priority->label(),
            ],
            'start_date' => $task->start_date?->toDateString(),
            'due_date' => $task->due_date?->toDateString(),
            'completed_at' => $task->completed_at?->toISOString(),
            'created_at' => $task->created_at?->toISOString(),
            'updated_at' => $task->updated_at?->toISOString(),
            'assignees' => $task->users
                ->map(fn (User $user): array => ['name' => $user->name])
                ->values()
                ->all(),
            'tags' => $task->tags
                ->map(fn (Tag $tag): array => [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'slug' => $tag->slug,
                ])
                ->values()
                ->all(),
            'web_url' => route('tasks.show', $task),
        ];
    }
}
