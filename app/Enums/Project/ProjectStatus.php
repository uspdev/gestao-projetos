<?php

namespace App\Enums\Project;

enum ProjectStatus: string
{
    case PLANNING = 'PLANNING';
    case DEVELOPMENT = 'DEVELOPMENT';
    case PRODUCTION = 'PRODUCTION';
    case DEACTIVATED = 'DEACTIVATED';
    case MIGRATED = 'MIGRATED';
    case HOLD = 'HOLD';

    public function label(): string
    {
        return match ($this) {
            self::PLANNING => 'Planejamento',
            self::DEVELOPMENT => 'Desenvolvimento',
            self::PRODUCTION => 'Produção',
            self::DEACTIVATED => 'Desativado',
            self::MIGRATED => 'Migrado',
            self::HOLD => 'Em Espera',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PLANNING => 'badge-light text-dark',
            self::DEVELOPMENT => 'badge-primary',
            self::PRODUCTION => 'badge-success',
            self::HOLD => 'badge-warning',
            self::MIGRATED => 'badge-info',
            self::DEACTIVATED => 'badge-dark',
        };
    }
}
