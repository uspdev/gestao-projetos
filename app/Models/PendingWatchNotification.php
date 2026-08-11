<?php

namespace App\Models;

use App\Contracts\Watchable;
use App\Enums\Watch\WatchEventType;
use App\Jobs\SendWatchDigest;
use App\Mail\MeetingWatchUpdate;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

class PendingWatchNotification extends Model
{
    protected $fillable = [
        'user_id',
        'watchable_type',
        'watchable_id',
        'event_type',
        'actor_id',
        'title',
        'summary',
        'details',
        'url',
        'occurred_at',
        'send_after',
    ];

    protected function casts(): array
    {
        return [
            'event_type' => WatchEventType::class,
            'occurred_at' => 'datetime',
            'send_after' => 'datetime',
        ];
    }

    public static function addForWatchers(
        Watchable&EloquentModel $watchable,
        WatchEventType $eventType,
        User $actor,
        string $summary,
        ?string $details,
        ?string $url,
    ): void {
        if (
            ! Schema::hasTable('watches')
            || ! Schema::hasTable('pending_watch_notifications')
            || ! $watchable->watchCanReceiveNotifications()
        ) {
            return;
        }

        $watcherIds = Watch::query()
            ->where('watchable_type', $watchable->getMorphClass())
            ->where('watchable_id', $watchable->getKey())
            ->where('user_id', '!=', $actor->id)
            ->pluck('user_id');

        foreach ($watcherIds as $userId) {
            static::addForUser(
                (int) $userId,
                $watchable,
                $eventType,
                $actor,
                $summary,
                $details,
                $url,
            );
        }
    }

    /**
     * Despacha notificações individuais para alterações de Reunião.
     *
     * Diferentemente dos demais eventos de acompanhamento, alterações de
     * Reunião não aguardam o intervalo do resumo.
     */
    public static function dispatchMeetingUpdateForWatchers(
        Meeting $meeting,
        User $actor,
        string $summary,
    ): void {
        if (! Schema::hasTable('watches') || ! $meeting->watchCanReceiveNotifications()) {
            return;
        }

        $watcherIds = Watch::query()
            ->where('watchable_type', $meeting->getMorphClass())
            ->where('watchable_id', $meeting->getKey())
            ->where('user_id', '!=', $actor->id)
            ->pluck('user_id');

        $watchers = User::query()
            ->whereIn('id', $watcherIds)
            ->get();

        foreach ($watchers as $watcher) {
            Mail::to($watcher->email)->queue(new MeetingWatchUpdate(
                $watcher,
                $actor,
                $meeting,
                $summary,
            ));
        }
    }

    private static function addForUser(
        int $userId,
        Watchable&EloquentModel $watchable,
        WatchEventType $eventType,
        User $actor,
        string $summary,
        ?string $details,
        ?string $url,
    ): void {
        DB::transaction(function () use (
            $userId,
            $watchable,
            $eventType,
            $actor,
            $summary,
            $details,
            $url,
        ): void {
            $user = User::query()->whereKey($userId)->lockForUpdate()->first();

            if (! $user || ! Watch::query()
                ->where('user_id', $userId)
                ->where('watchable_type', $watchable->getMorphClass())
                ->where('watchable_id', $watchable->getKey())
                ->exists()) {
                return;
            }

            $occurredAt = now();
            $sendAfter = $occurredAt->copy()->addMinutes(
                (int) config('projetos.watching.digest_minutes', 5)
            );

            static::query()
                ->where('user_id', $userId)
                ->update(['send_after' => $sendAfter]);

            $pending = static::query()->create([
                'user_id' => $userId,
                'watchable_type' => $watchable->getMorphClass(),
                'watchable_id' => $watchable->getKey(),
                'event_type' => $eventType,
                'actor_id' => $actor->id,
                'title' => $watchable->watchLabel(),
                'summary' => $summary,
                'details' => $details,
                'url' => $url,
                'occurred_at' => $occurredAt,
                'send_after' => $sendAfter,
            ]);

            DB::afterCommit(
                fn() => SendWatchDigest::dispatch($pending->id)->delay($sendAfter)
            );
        });
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    public function watchable(): MorphTo
    {
        return $this->morphTo()->withTrashed();
    }
}
