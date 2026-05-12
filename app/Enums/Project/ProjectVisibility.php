<?php

namespace App\Enums\Project;

enum ProjectVisibility: string
{
    case PUBLIC = 'PUBLIC';
    case AUTHENTICATED = 'AUTHENTICATED';
    case PRIVATE = 'PRIVATE';

    public function label(): string
    {
        return match ($this) {
            self::PUBLIC => 'Público',
            self::AUTHENTICATED => 'Usuários Autenticados',
            self::PRIVATE => 'Privado',
        };
    }
}
