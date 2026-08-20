<?php

namespace App\Models;

use App\Contracts\Watchable;
use App\Jobs\SendWatchDigest;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Watch extends Model
{
    public const GENERAL_MENTION_TYPE = 'mention';
    private const DISABLED_MENTION_TYPE = 'mention_disabled';

    /**
     * Recurso ao qual esta preferência de acompanhamento pertence.
     */
    public function watchable(): MorphTo
    {
        return $this->morphTo();
    }

    public static function enableFor(int $userId, Watchable&EloquentModel $watchable): void
    {
        static::enableForUsers([$userId], $watchable);
    }

    /**
     * Ativa o acompanhamento para cada pessoa indicada, sem duplicar a preferência.
     *
     * @param iterable<int> $userIds
     */
    public static function enableMentionFor(int $userId): void
    {
        if (! Schema::hasTable('watches')) {
            return;
        }

        // Se o usuário já tiver desativado menções, não reative automaticamente.
        if (static::query()
            ->where('user_id', $userId)
            ->where('watchable_type', self::DISABLED_MENTION_TYPE)
            ->where('watchable_id', $userId)
            ->exists()
        ) {
            return;
        }

        static::activateMentionFor($userId);
    }

    /**
     * Ativa explicitamente o acompanhamento geral de Menções para o usuário.
     *
     * Remove eventual marcador de opt-out e grava o acompanhamento ativo
     * reutilizando a tabela watches.
     */
    public static function activateMentionFor(int $userId): void
    {
        if (! Schema::hasTable('watches')) {
            return;
        }

        $now = now();

        // Remove o registro que representa o opt-out do usuário
        // Se essa linha existir, significa que o usuário havia desativado notificações de Menções.
        // Ao ativar novamente, ela precisa ser removida
        static::query()
            ->where('user_id', $userId)
            ->where('watchable_type', self::DISABLED_MENTION_TYPE)
            ->where('watchable_id', $userId)
            ->delete();

        static::query()->upsert([[
            'user_id' => $userId,
            'watchable_type' => self::GENERAL_MENTION_TYPE,
            'watchable_id' => $userId,
            'created_at' => $now,
            'updated_at' => $now,
        ]], ['user_id', 'watchable_type', 'watchable_id'], ['updated_at']);
    }

    /**
     * Verifica se o usuário tem acompanhamento geral de Menções ativo.
     */
    public static function mentionEnabledFor(int $userId): bool
    {
        if (! Schema::hasTable('watches')) {
            return true;
        }

        return static::query()
            ->where('user_id', $userId)
            ->where('watchable_type', self::GENERAL_MENTION_TYPE)
            ->where('watchable_id', $userId)
            ->exists();
    }

    public static function disableMentionFor(int $userId): void
    {
        if (! Schema::hasTable('watches')) {
            return;
        }

        // Remove o registro que representa o acompanhamento geral de Menções
        // Se essa linha existir, significa que o usuário havia ativado notificações de Menções.
        // Ao desativar, ela precisa ser removida
        DB::transaction(function () use ($userId): void {
            User::query()->whereKey($userId)->lockForUpdate()->first();

            static::query()
                ->where('user_id', $userId)
                ->where('watchable_type', self::GENERAL_MENTION_TYPE)
                ->where('watchable_id', $userId)
                ->delete();

            // Adiciona um registro que representa o opt-out do usuário
            // Se essa linha existir, significa que o usuário havia desativado notificações de Menções.
            static::query()->upsert([[
                'user_id' => $userId,
                'watchable_type' => self::DISABLED_MENTION_TYPE,
                'watchable_id' => $userId,
                'created_at' => now(),
                'updated_at' => now(),
            ]], ['user_id', 'watchable_type', 'watchable_id'], ['updated_at']);
            // Se houver notificações pendentes de Menções, elas devem ser removidas, e o envio do resumo deve ser reagendado para o futuro.
            if (Schema::hasTable('pending_watch_notifications')) {
                PendingWatchNotification::query()
                    ->where('user_id', $userId)
                    ->where('event_type', \App\Enums\Watch\WatchEventType::MENTION_CREATED->value)
                    ->delete();

                $sendAfter = now()->addMinutes(
                    (int) config('projetos.watching.digest_minutes', 5)
                );

                // Reagendar o envio do resumo de Menções para o futuro, caso haja notificações pendentes.
                PendingWatchNotification::query()
                    ->where('user_id', $userId)
                    ->update(['send_after' => $sendAfter]);

                $latestId = PendingWatchNotification::query()
                    ->where('user_id', $userId)
                    ->max('id');

                if ($latestId) {
                    DB::afterCommit(
                        fn() => SendWatchDigest::dispatch((int) $latestId)->delay($sendAfter)
                    );
                }
            }
        });
    }

    /**
     * Ativa o acompanhamento para cada pessoa indicada, sem duplicar a preferência.
     *
     * @param iterable<int> $userIds
     */
    public static function enableForUsers(iterable $userIds, Watchable&EloquentModel $watchable): void
    {
        if (! Schema::hasTable('watches') || ! $watchable->watchCanReceiveNotifications()) {
            return;
        }

        $userIds = collect($userIds)
            ->filter(fn($userId) => is_numeric($userId) && (int) $userId > 0)
            ->map(fn($userId) => (int) $userId)
            ->unique()
            ->values();

        if ($userIds->isEmpty()) {
            return;
        }

        $now = now();

        static::query()->upsert(
            $userIds->map(fn(int $userId) => [
                'user_id' => $userId,
                'watchable_type' => $watchable->getMorphClass(),
                'watchable_id' => $watchable->getKey(),
                'created_at' => $now,
                'updated_at' => $now,
            ])->all(),
            ['user_id', 'watchable_type', 'watchable_id'],
            ['updated_at'],
        );
    }

    public static function disableFor(int $userId, Watchable&EloquentModel $watchable): void
    {
        if (
            ! Schema::hasTable('watches')
            || ! Schema::hasTable('pending_watch_notifications')
        ) {
            return;
        }

        DB::transaction(function () use ($userId, $watchable): void {
            User::query()->whereKey($userId)->lockForUpdate()->first();

            $target = [
                'watchable_type' => $watchable->getMorphClass(),
                'watchable_id' => $watchable->getKey(),
            ];

            static::query()
                ->where('user_id', $userId)
                ->where($target)
                ->delete();

            PendingWatchNotification::query()
                ->where('user_id', $userId)
                ->where($target)
                ->delete();

            $sendAfter = now()->addMinutes(
                (int) config('projetos.watching.digest_minutes', 5)
            );

            PendingWatchNotification::query()
                ->where('user_id', $userId)
                ->update(['send_after' => $sendAfter]);

            $latestId = PendingWatchNotification::query()
                ->where('user_id', $userId)
                ->max('id');

            if ($latestId) {
                DB::afterCommit(
                    fn() => SendWatchDigest::dispatch((int) $latestId)->delay($sendAfter)
                );
            }
        });
    }
}
