<?php

namespace App\Enums\Task;

enum TaskLabel: string
{
    case FIX = 'FIX';
    case FEATURE = 'FEATURE';
    case TEST = 'TEST';
    case DOC = 'DOC';
    case REFACTOR = 'REFACTOR';

    public function label(): string
    {
        return match ($this) {
            self::FIX => 'Correção',
            self::FEATURE => 'Funcionalidade',
            self::TEST => 'Teste',
            self::DOC => 'Documentação',
            self::REFACTOR => 'Refatoração',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::FIX => 'badge-danger',
            self::FEATURE => 'badge-success',
            self::TEST => 'badge-primary',
            self::DOC => 'badge-info',
            self::REFACTOR => 'badge-warning',
        };
    }
}