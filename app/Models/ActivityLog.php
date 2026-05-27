<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Activity;

class ActivityLog extends Activity
{
    // Query scopes for common audit filters.
    public function scopeInLog(Builder $query, ...$logNames): Builder
    {
        return $query->whereIn('log_name', $logNames);
    }

    public function scopeCausedBy(Builder $query, \Illuminate\Database\Eloquent\Model $causer): Builder
    {
        return $query
            ->where('causer_type', $causer->getMorphClass())
            ->where('causer_id', $causer->getKey());
    }

    public function scopeForSubject(Builder $query, \Illuminate\Database\Eloquent\Model $subject): Builder
    {
        return $query
            ->where('subject_type', $subject->getMorphClass())
            ->where('subject_id', $subject->getKey());
    }

    public function scopeForEvent(Builder $query, string $event): Builder
    {
        return $query->where('event', $event);
    }

    public function getOldValuesAttribute(): array
    {
        return $this->properties->get('old', []);
    }

    public function getNewValuesAttribute(): array
    {
        return $this->properties->get('attributes', []);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'causer_id');
    }
}
