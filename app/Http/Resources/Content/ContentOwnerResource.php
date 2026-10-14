<?php

namespace App\Http\Resources\Content;

use App\Models\Meeting;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContentOwnerResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        return match (true) {
            $this->resource instanceof Project => [
                'type' => 'project',
                'id' => $this->resource->id,
                'slug' => $this->resource->slug,
                'name' => $this->resource->name,
            ],
            $this->resource instanceof Task => [
                'type' => 'task',
                'id' => $this->resource->id,
                'title' => $this->resource->title,
            ],
            $this->resource instanceof Meeting => [
                'type' => 'meeting',
                'id' => $this->resource->id,
                'title' => $this->resource->title,
            ],
        };
    }
}
