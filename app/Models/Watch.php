<?php

namespace App\Models;

use App\Contracts\Watchable;
use App\Jobs\SendWatchDigest;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class Watch extends Model
{
    public static function enableFor(int $userId, Watchable&EloquentModel $watchable): void
    {
        if (! Schema::hasTable('watches')) {
            return;
        }

        $now = now();

        static::query()->upsert([[
            'user_id' => $userId,
            'watchable_type' => $watchable->getMorphClass(),
            'watchable_id' => $watchable->getKey(),
            'created_at' => $now,
            'updated_at' => $now,
        ]], ['user_id', 'watchable_type', 'watchable_id'], ['updated_at']);
    }

    public static function disableFor(int $userId, Watchable&EloquentModel $watchable): void
    {
        if (! Schema::hasTable('watches')
            || ! Schema::hasTable('pending_watch_notifications')) {
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
                    fn () => SendWatchDigest::dispatch((int) $latestId)->delay($sendAfter)
                );
            }
        });
    }
}
