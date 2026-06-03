<?php

namespace App\Models;

use App\Enums\Project\ProjectUserRole;
use Illuminate\Database\Eloquent\Relations\Pivot;
use App\Traits\ResolvesAuditOwner;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectUser extends Pivot
{
    use ResolvesAuditOwner;

    protected $casts = [
        'role' => ProjectUserRole::class,
        'pinned' => 'bool',
    ];

    protected $touches = ['project'];

    protected static function booted(): void
    {
        static::created(function (ProjectUser $pivot) {
            activity()
                ->useLog('project')
                ->event('attached')
                ->performedOn(static::resolveOwner(Project::class, $pivot->project_id))
                ->withProperties([
                    'attributes' => [
                        'user_id' => $pivot->user_id,
                        'role' => $pivot->role,
                    ],
                ])
                ->log('attached');
        });

        static::updated(function (ProjectUser $pivot) {
            if (!$pivot->isDirty(['role', 'pinned'])) {
                return;
            }

            activity()
                ->useLog('project')
                ->event('updated')
                ->performedOn(static::resolveOwner(Project::class, $pivot->project_id))
                ->withProperties([
                    'attributes' => [
                        'user_id' => $pivot->user_id,
                        'role' => $pivot->role,
                        'pinned' => $pivot->pinned,
                    ],
                    'old' => [
                        'user_id' => $pivot->user_id,
                        'role' => $pivot->getOriginal('role'),
                        'pinned' => $pivot->getOriginal('pinned'),
                    ],
                ])
                ->log('updated');
        });

        static::deleted(function (ProjectUser $pivot) {
            activity()
                ->useLog('project')
                ->event('detached')
                ->performedOn(static::resolveOwner(Project::class, $pivot->project_id))
                ->withProperties([
                    'old' => [
                        'user_id' => $pivot->user_id,
                        'role' => $pivot->role,
                    ],
                ])
                ->log('detached');
        });
    }

    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()
            ->useLogName('project')
            ->logOnly(['role', 'pinned'])
            ->dontSubmitEmptyLogs();
    }

    // =========== Relacionamentos =============
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
