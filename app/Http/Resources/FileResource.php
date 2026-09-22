<?php

namespace App\Http\Resources;

use App\Models\Media;
use App\Models\Meeting;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FileResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Media $file */
        $file = $this->resource;

        return [
            'uuid' => $file->uuid,
            'name' => $file->display_name,
            'extension' => strtolower((string) pathinfo($file->file_name, PATHINFO_EXTENSION)),
            'mime_type' => $file->mime_type,
            'size' => $file->size,
            'uploaded_at' => $file->created_at?->toISOString(),
            'owner' => $this->ownerSummary($file->model),
            'download_url' => route('api.projects.files.show', [
                'project' => $request->route('project'),
                'uuid' => $file->uuid,
            ]),
        ];
    }

    /** @return array<string, mixed> */
    private function ownerSummary(Project|Task|Meeting $owner): array
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
        };
    }
}
