<?php

namespace App\Traits;

use App\Models\Mention;
use App\Models\Comment;
use App\Models\Meeting;
use App\Models\MeetingItem;
use App\Services\Mentions\MentionManager;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;

trait HasMentions
{
    public static function bootHasMentions(): void
    {
        static::deleted(function (self $source): void {
            if (! Schema::hasTable('mentions')) {
                return;
            }

            $source->outgoingMentions()->delete();

            if (method_exists($source, 'isForceDeleting') && $source->isForceDeleting()) {
                $source->incomingMentions()->delete();
            }

            if ($source instanceof Meeting && Schema::hasTable('meeting_items')) {
                MeetingItem::query()
                    ->where('meeting_id', $source->getKey())
                    ->get()
                    ->each(fn (MeetingItem $item) => $item->outgoingMentions()->delete());
            }
        });

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            static::restored(function (self $source): void {
                if (! Schema::hasTable('mentions')) {
                    return;
                }

                app(MentionManager::class)->rebuildSource($source);

                if ($source instanceof Meeting && Schema::hasTable('meeting_items')) {
                    MeetingItem::query()
                        ->where('meeting_id', $source->getKey())
                        ->get()
                        ->each(fn (MeetingItem $item) => app(MentionManager::class)->rebuildSource($item));
                }
            });
        }

        static::updated(function (self $source): void {
            if (! $source instanceof Comment || ! $source->wasChanged('is_active')
                || ! Schema::hasTable('mentions')) {
                return;
            }

            if ($source->is_active) {
                app(MentionManager::class)->rebuildSource($source);
            } else {
                $source->outgoingMentions()->delete();
            }
        });
    }

    public function outgoingMentions(): MorphMany
    {
        return $this->morphMany(Mention::class, 'source');
    }

    public function incomingMentions(): MorphMany
    {
        return $this->morphMany(Mention::class, 'target');
    }
}
