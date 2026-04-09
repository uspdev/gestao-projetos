<?php

namespace App\Enums\Project;

enum ProjectUserRole: string
{
    case OWNER = 'OWNER';
    case MEMBER = 'MEMBER';
    case VIEWER = 'VIEWER';

    public function label(): string
    {
        return match ($this) {
            self::OWNER => 'Responsavel',
            self::MEMBER => 'Membro',
            self::VIEWER => 'Visualizador',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::OWNER => 'badge-primary',
            self::MEMBER => 'badge-info',
            self::VIEWER => 'badge-secondary',
        };
    }
}