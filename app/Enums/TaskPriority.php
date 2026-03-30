<?php

namespace App\Enums;

enum TaskPriority: int
{
    case URGENT = 1;
    case HIGH = 2;
    case MEDIUM = 3;
    case LOW = 4;

    public function label(): string
    {
        return match($this) {
            self::URGENT => 'Urgente',
            self::HIGH => 'Alta',
            self::MEDIUM => 'Média',
            self::LOW => 'Baixa',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::URGENT => 'bg-red-500',
            self::HIGH => 'bg-orange-500',
            self::MEDIUM => 'bg-yellow-500',
            self::LOW => 'bg-blue-500',
        };
    }
}