<?php

namespace App\Traits;

use App\Enums\Meeting\MeetingStatus;
use App\Models\Meeting;
use App\Models\MeetingItem;
use App\Models\MeetingProject;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasMeeting
{
    /**
     * Relacionamento com reunioes N-N
     */
    public function meetings(): BelongsToMany
    {
        return $this->belongsToMany(Meeting::class, 'meeting_projects')
            ->using(MeetingProject::class);
    }

    /**
     * Relacionamento com meeting items via morph (Project pode ser um meeting item)
     */
    public function meetingItems(): MorphMany
    {
        return $this->morphMany(MeetingItem::class, 'discussable');
    }

    /**
     *  Scope models available for meetings
     */
    public function scopeAvailableForMeetings(Builder $query, User $user): Builder
    {
        return $query
            ->accessibleBy($user)
            ->whereHas('modules', function (Builder $q) {
                $q->where('modules.slug', 'meetings')
                    ->where('project_modules.enabled', true);
            });
    }

    public function meetingsCount(bool $showCompleted = false): int
    {
        return $this->meetings()
            ->when(! $showCompleted, function ($query) {
                $query->where(
                    'status',
                    '!=',
                    MeetingStatus::COMPLETED->value
                );
            })
            ->count();
    }

    public function getIncompleteMeetingsCount(): int
    {
        return $this->meetings()
            ->where('status', '!=', \App\Enums\Meeting\MeetingStatus::COMPLETED)
            ->count();
    }
}
