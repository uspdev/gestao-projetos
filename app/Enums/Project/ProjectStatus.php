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
            self::DRAFT, self::PLANNED => 'badge-light text-dark',
            self::ACTIVE => 'badge-primary',
            self::COMPLETED => 'badge-success',
            self::HOLD => 'badge-warning',
            self::CANCELLED => 'badge-danger',
            self::ARCHIVED => 'badge-dark',
        };
    }
}
