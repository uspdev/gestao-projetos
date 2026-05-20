<?php

namespace App\Morphs;

class DiscussableMap extends BaseMorphMap
{
    public static function options(): array
    {
        return config('projetos.morphs.discussable', []);
    }

    protected static function contract(): ?string
    {
        return Discussable::class;
    }
}
