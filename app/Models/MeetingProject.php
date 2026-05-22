<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use App\Traits\ResolvesAuditOwner;

class MeetingProject extends Pivot
{
    use ResolvesAuditOwner;

    public $incrementing = true;

    protected $table = 'meeting_projects';

    protected static function booted(): void
    {
        static::created(function (MeetingProject $pivot) {
            activity()
                ->useLog('meeting')
                ->event('attached')
                ->performedOn(static::resolveOwner(Meeting::class, $pivot->meeting_id))
                ->withProperties([
                    'attributes' => [
                        'project_id' => $pivot->project_id,
                    ],
                ])
                ->log('attached');
        });

        static::deleted(function (MeetingProject $pivot) {
            activity()
                ->useLog('meeting')
                ->event('detached')
                ->performedOn(static::resolveOwner(Meeting::class, $pivot->meeting_id))
                ->withProperties([
                    'old' => [
                        'project_id' => $pivot->project_id,
                    ],
                ])
                ->log('detached');
        });
    }
}