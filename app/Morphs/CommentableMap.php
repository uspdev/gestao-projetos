<?php

namespace App\Morphs;

class CommentableMap extends BaseMorphMap
{
    public static function options(): array
    {
        return config('projetos.morphs.commentable', []);
    }

    protected static function contract(): ?string
    {
        return null; // Não exige interface/contrato 
    }
}
