<?php

namespace App\Http\Resources;

use App\Models\Meeting;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MeetingResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Meeting $meeting */
        $meeting = $this->resource;
        return [
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
    }
}
