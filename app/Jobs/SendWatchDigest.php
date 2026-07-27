<?php

namespace App\Jobs;

use App\Mail\WatchDigest;
use App\Models\PendingWatchNotification;
use App\Models\User;
use App\Models\Watch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class SendWatchDigest implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 4;

    public function __construct(public int $latestPendingId)
    {
    }

    public function backoff(): array
    {
        return [60, 300, 900];
    }

    public function handle(): void
    {
        DB::transaction(function (): void {
            $trigger = PendingWatchNotification::query()->find($this->latestPendingId);

            if (! $trigger) {
                return;
            }

            $user = User::query()->whereKey($trigger->user_id)->lockForUpdate()->first();

            if (! $user) {
                return;
            }

            $latest = PendingWatchNotification::query()
                ->where('user_id', $user->id)
                ->latest('id')
                ->first();

            if (! $latest
                || $latest->id !== $this->latestPendingId
                || $latest->send_after->isFuture()) {
                return;
            }

            $pending = PendingWatchNotification::query()
                ->with(['actor', 'watchable'])
                ->where('user_id', $user->id)
                ->oldest('occurred_at')
                ->get();

            $valid = $pending->filter(function (PendingWatchNotification $notification) use ($user) {
                $watchable = $notification->watchable;

                return $watchable
                    && $watchable->watchCanBeViewedBy($user)
                    && Watch::query()
                        ->where('user_id', $user->id)
                        ->where('watchable_type', $notification->watchable_type)
                        ->where('watchable_id', $notification->watchable_id)
                        ->exists();
            })->values();

            if ($valid->isNotEmpty()) {
                Mail::to($user->email)->send(new WatchDigest($user, $valid));
            }

            PendingWatchNotification::query()
                ->whereIn('id', $pending->pluck('id'))
                ->delete();
        });
    }
}
