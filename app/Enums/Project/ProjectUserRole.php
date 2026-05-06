<?php

namespace App\Enums\Project;

enum ProjectUserRole: string
{
    case OWNER = 'OWNER';
    case CONTRIBUTOR = 'CONTRIBUTOR';
    case VIEWER = 'VIEWER';

    public function label(): string
    {
        return match ($this) {
            self::OWNER => 'Dono',
            self::CONTRIBUTOR => 'Colaborador',
            self::VIEWER => 'Visualizador',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::OWNER => 'badge-success',
            self::CONTRIBUTOR => 'badge-primary',
            self::VIEWER => 'badge-secondary',
        };
    }
}
