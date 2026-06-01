<?php

namespace App\Enums\Task;

/**
 * NEW: não possui responsáveis
 * ASSIGNED: ao atribuir um responsável mudar para assigned.
 *  Voltar para NEW se remover todos responsáveis
 * IN_PROGRESS
 */
enum TaskStatus: string
{
    case NEW = 'NEW';
    case ASSIGNED = 'ASSIGNED';
    case IN_PROGRESS = 'IN_PROGRESS';
    case IN_REVIEW = 'IN_REVIEW';
    case HOLD = 'HOLD';
    case DONE = 'DONE';

    public function label(): string
    {
        return match ($this) {
            self::NEW => 'Nova',
            self::ASSIGNED => 'Atribuída',
            self::IN_PROGRESS => 'Em Andamento',
            self::IN_REVIEW => 'Em Revisão',
            self::HOLD => 'Em Espera',
            self::DONE => 'Concluída',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NEW => 'badge-warning',
            self::ASSIGNED => 'badge-success',
            self::IN_PROGRESS => 'badge-primary',
            self::IN_REVIEW => 'badge-info',
            self::HOLD => 'badge-warning',
            self::DONE => 'badge-secondary',
        };
    }

    /**
     * Retorna os status disponíveis para o Kanban
     *
     * ou seja, exclui o status NEW
     * Se $taskDOne = 1, inclui o status DONE
     */
    public static function availableForKanban($tasksDone = 0)
    {
        $reject = ($tasksDone == 1) ? null : self::DONE;
        return collect(self::cases())
            ->reject(
                fn(self $status) =>
                $status === self::NEW || $status === $reject
            )
            ->values();
    }
}
