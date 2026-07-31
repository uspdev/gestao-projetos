<?php

namespace App\Services\Mentions;

final readonly class MentionReference
{
    public function __construct(
        public string $type,
        public string $key,
    ) {
    }

    public function identity(): string
    {
        return $this->type . ':' . $this->key;
    }
}
