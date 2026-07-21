<?php

namespace App\Morphs;

class DuplicableMap extends BaseMorphMap
{
    public static function options(): array
    {
        return config('projetos.morphs.duplicable', []);
    }

    protected static function contract(): ?string
    {
        return Duplicable::class;
    }
}
