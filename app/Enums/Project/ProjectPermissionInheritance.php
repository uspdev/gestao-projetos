<?php

namespace App\Enums\Project;

enum ProjectPermissionInheritance: string
{
    case NONE = 'NONE';
    case READ = 'READ';
    case FULL = 'FULL';

    public function label(): string
    {
        return match ($this) {
            self::NONE => 'Sem Herança',
            self::READ => 'Apenas Leitura',
            self::FULL => 'Herança Total',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NONE => 'badge-secondary',
            self::READ => 'badge-info',
            self::FULL => 'badge-success',
        };
    }
}
