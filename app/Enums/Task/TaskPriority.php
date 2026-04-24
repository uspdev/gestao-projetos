<?php

namespace App\Enums\Task;

enum TaskPriority: int
{
    case URGENT = 1;
    case HIGH = 2;
    case MEDIUM = 3;
    case LOW = 4;

    public function label(): string
    {
        return match ($this) {
            self::URGENT => 'Urgente',
            self::HIGH => 'Alta',
            self::MEDIUM => 'Média',
            self::LOW => 'Baixa',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::URGENT => 'badge-danger',
            self::HIGH => 'badge-warning',
            self::MEDIUM => 'badge-secondary',
            self::LOW => 'badge-light',
        };
    }
}
