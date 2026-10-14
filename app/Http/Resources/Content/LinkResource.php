<?php

namespace App\Http\Resources\Content;

use App\Models\Link;
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
            'owner' => $link->linkable ? (new ContentOwnerResource($link->linkable))->resolve($request) : null,
        ];
    }
}
