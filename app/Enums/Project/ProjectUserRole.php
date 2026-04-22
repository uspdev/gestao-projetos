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
            self::OWNER => 'Dono',
            self::MEMBER => 'Membro',
            self::VIEWER => 'Visualizador',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::OWNER => 'badge-success',
            self::MEMBER => 'badge-primary',
            self::VIEWER => 'badge-secondary',
        };
    }
}