<?php

namespace App\Enums\Meeting;

enum MeetingStatus: string
{
    case DRAFT = 'DRAFT';
    case SCHEDULED = 'SCHEDULED';
    case ONGOING = 'ONGOING';
    case COMPLETED = 'COMPLETED';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Rascunho',
            self::SCHEDULED => 'Agendada',
            self::ONGOING => 'Em Andamento',
            self::COMPLETED => 'Concluída',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'light',
            self::SCHEDULED => 'success',
            self::ONGOING => 'primary',
            self::COMPLETED => 'secondary',
        };
    }
}
