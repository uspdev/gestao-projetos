<?php

namespace App\Enums\Project;

enum ProjectStatus: string
{
    case DRAFT = 'DRAFT';
    case PLANNED = 'PLANNED';
    case ACTIVE = 'ACTIVE';
    case HOLD = 'HOLD';            
    case COMPLETED = 'COMPLETED';
    case CANCELLED = 'CANCELLED';
    case ARCHIVED = 'ARCHIVED';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Rascunho',
            self::PLANNED => 'Planejado',
            self::ACTIVE => 'Ativo',
            self::HOLD => 'Em Espera',
            self::COMPLETED => 'Concluído',
            self::CANCELLED => 'Cancelado',
            self::ARCHIVED => 'Arquivado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT, self::PLANNED => 'light text-dark',
            self::ACTIVE => 'primary',
            self::COMPLETED => 'success',
            self::HOLD => 'warning',
            self::CANCELLED => 'danger',
            self::ARCHIVED => 'dark',
        };
    }
}
