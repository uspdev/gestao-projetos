<?php

namespace App\Http\Resources\Shared;

use App\Http\Resources\Content\FileResource;
use App\Http\Resources\Content\LinkResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

abstract class ApiDetailResource extends JsonResource
{
    /** @return array<string, mixed> */
    protected function visibleDetail(Request $request): array
    {
        return [
            'comments' => $this->relationArray(
                'apiComments',
                fn ($comment): array => (new CommentResource($comment))->resolve($request),
            ),
            'files' => [
                'owned' => $this->relationArray(
                    'apiOwnedFiles',
                    fn ($file): array => (new FileResource($file))->resolve($request),
                ),
                'shared' => $this->relationArray(
                    'apiSharedFiles',
                    fn ($file): array => (new FileResource($file))->resolve($request),
                ),
            ],
            'links' => [
                'owned' => $this->relationArray(
                    'apiOwnedLinks',
                    fn ($link): array => (new LinkResource($link))->resolve($request),
                ),
                'shared' => $this->relationArray(
                    'apiSharedLinks',
                    fn ($link): array => (new LinkResource($link))->resolve($request),
                ),
            ],
            'incoming_mentions' => $this->resource->getRelation('apiIncomingMentions'),
        ];
    }

    /** @return list<array<string, mixed>> */
    private function relationArray(string $relation, callable $present): array
    {
        return collect($this->resource->getRelation($relation))
            ->map($present)
            ->values()
            ->all();
    }
}
