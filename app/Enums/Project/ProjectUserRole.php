<?php

namespace App\Enums\Project;

enum ProjectUserRole: string
{
    case ADMIN = 'ADMIN';
    case CONTRIBUTOR = 'CONTRIBUTOR';
    case VIEWER = 'VIEWER';

    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'Admin',
            self::CONTRIBUTOR => 'Colaborador',
            self::VIEWER => 'Visualizador',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ADMIN => 'badge-success',
            self::CONTRIBUTOR => 'badge-primary',
            self::VIEWER => 'badge-secondary',
        };
    }
}
