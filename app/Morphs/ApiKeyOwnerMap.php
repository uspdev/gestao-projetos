<?php

namespace App\Morphs;

class ApiKeyOwnerMap extends BaseMorphMap
{
    public static function options(): array
    {
        return config('api-keys.owners', []);
    }

    protected static function contract(): ?string
    {
        return null;
    }
}
