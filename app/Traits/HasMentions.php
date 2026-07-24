<?php

namespace App\Traits;

use App\Models\Mention;
use App\Services\MentionIndexer;
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

            $source->mentions()->delete();
        });

        if (in_array(SoftDeletes::class, class_uses_recursive(static::class), true)) {
            static::restored(function (self $source): void {
                if (! Schema::hasTable('mentions')) {
                    return;
                }

                app(MentionIndexer::class)->rebuildSource($source);
            });
        }
    }

    public function mentions(): MorphMany
    {
        return $this->morphMany(Mention::class, 'mentionable');
    }
}
