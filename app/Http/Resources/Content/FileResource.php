<?php

namespace App\Http\Resources\Content;

use App\Models\Media;
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
            'owner' => (new ContentOwnerResource($file->model))->resolve($request),
            'download_url' => route('api.projects.files.show', [
                'project' => $request->route('project'),
                'uuid' => $file->uuid,
            ]),
        ];
    }
}
