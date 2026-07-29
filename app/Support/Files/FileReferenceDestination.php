<?php

namespace App\Support\Files;

final readonly class FileReferenceDestination
{
    public function __construct(
        public string $url,
        public bool $opensInNewTab,
    ) {}
}
