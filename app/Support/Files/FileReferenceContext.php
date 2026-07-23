<?php

namespace App\Support\Files;

final readonly class FileReferenceContext
{
    private function __construct(
        public string $type,
        public ?int $id,
        public ?string $commentableType,
        public ?int $commentableId,
    ) {
    }

    /** @param array{context_type: string, context_id?: int|string|null, commentable_type?: string|null, commentable_id?: int|string|null} $validated */
    public static function fromValidated(array $validated): self
    {
        return new self(
            type: $validated['context_type'],
            id: isset($validated['context_id']) ? (int) $validated['context_id'] : null,
            commentableType: $validated['commentable_type'] ?? null,
            commentableId: isset($validated['commentable_id']) ? (int) $validated['commentable_id'] : null,
        );
    }
}
