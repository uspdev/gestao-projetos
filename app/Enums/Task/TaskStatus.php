<?php

namespace App\Enums\Task;

enum TaskStatus: string
{
    case TO_DO = 'TO_DO';
    case IN_PROGRESS = 'IN_PROGRESS';
    case IN_REVIEW = 'IN_REVIEW';
    case HOLD = 'HOLD';
    case DONE = 'DONE';

    public function label(): string
    {
        return match ($this) {
            self::TO_DO => 'Atribuido',
            self::IN_PROGRESS => 'Em Andamento',
            self::IN_REVIEW => 'Em Revisão',
            self::HOLD => 'Em Espera',
            self::DONE => 'Concluída',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::TO_DO => 'badge-success',
            self::IN_PROGRESS => 'badge-primary',
            self::IN_REVIEW => 'badge-info',
            self::HOLD => 'badge-warning',
            self::DONE => 'badge-secondary',
        };
    }
}
