<?php

namespace App\Http\Resources;

use App\Models\Link;
use App\Models\Meeting;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LinkResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Link $link */
        $link = $this->resource;

        return [
            'uuid' => $link->uuid,
            'name' => $link->display_name,
            'url' => $link->url,
            'created_at' => $link->created_at?->toISOString(),
            'owner' => $this->ownerSummary($link->linkable),
        ];
    }

    /** @return array<string, mixed>|null */
    private function ownerSummary(Project|Task|Meeting|null $owner): ?array
    {
        return match (true) {
            $owner instanceof Project => [
                'type' => 'project',
                'id' => $owner->id,
                'slug' => $owner->slug,
                'name' => $owner->name,
            ],
            $owner instanceof Task => [
                'type' => 'task',
                'id' => $owner->id,
                'title' => $owner->title,
            ],
            $owner instanceof Meeting => [
                'type' => 'meeting',
                'id' => $owner->id,
                'title' => $owner->title,
            ],
            default => null,
        };
    }
}
