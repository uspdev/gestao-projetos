<?php

namespace App\Enums\Project;

// Usado no seed do banco para definir as fases do projeto
enum ProjectPhase: string
{
    case PLANNING = 'PLANNING';
    case DEVELOPMENT = 'DEVELOPMENT';
    case PRODUCTION = 'PRODUCTION';
    case RETIRED = 'RETIRED';

    public function label(): string
    {
        return match ($this) {
            self::PLANNING => 'Planejamento',
            self::DEVELOPMENT => 'Desenvolvimento',
            self::PRODUCTION => 'Produção',
            self::RETIRED => 'Aposentado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PLANNING => 'badge-light text-dark',
            self::DEVELOPMENT => 'badge-primary',
            self::PRODUCTION => 'badge-success',
            self::RETIRED => 'badge-dark',
        };
    }
}
