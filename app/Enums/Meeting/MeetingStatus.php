<?php

namespace App\Enums\Meeting;

enum MeetingStatus: string
{
    case SCHEDULED = 'SCHEDULED';
    case ONGOING = 'ONGOING';
    case COMPLETED = 'COMPLETED';

    public function label(): string
    {
        return match ($this) {
            self::SCHEDULED => 'Agendada',
            self::ONGOING => 'Em Andamento',
            self::COMPLETED => 'Concluída',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::SCHEDULED => 'badge-light text-dark',
            self::ONGOING => 'badge-primary',
            self::COMPLETED => 'badge-success',
        };
    }
}
