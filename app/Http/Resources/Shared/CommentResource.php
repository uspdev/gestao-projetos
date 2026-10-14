<?php

namespace App\Http\Resources\Shared;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    /** @return array<string, mixed> */
    public function toArray(Request $request): array
    {
        /** @var Comment $comment */
        $comment = $this->resource;
        $author = $comment->user;

        return [
            'id' => $comment->id,
            'text' => $comment->text,
            'created_at' => $comment->created_at?->toISOString(),
            'updated_at' => $comment->updated_at?->toISOString(),
            'author' => $author ? (new UserResource($author))->resolve($request) : null,
        ];
    }
}
