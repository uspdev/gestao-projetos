<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use App\Traits\ResolvesAuditOwner;

class TaskUser extends Pivot
{   
    use ResolvesAuditOwner;

    public $incrementing = true;

    protected $table = 'task_user';

    protected static function booted(): void
    {
        static::created(function (TaskUser $pivot) {
            activity()
                ->useLog('task')
                ->event('attached')
                ->performedOn(static::resolveOwner(Task::class, $pivot->task_id))
                ->withProperties([
                    'attributes' => [
                        'user_id' => $pivot->user_id,
                    ],
                ])
                ->log('attached');
        });

        static::deleted(function (TaskUser $pivot) {
            activity()
                ->useLog('task')
                ->event('detached')
                ->performedOn(static::resolveOwner(Task::class, $pivot->task_id))
                ->withProperties([
                    'old' => [
                        'user_id' => $pivot->user_id,
                    ],
                ])
                ->log('detached');
        });
    }

    /**
     * Lido pelo PivotAuditSubscriber para filtrar campos no evento updated.
     * Não usa LogsActivity — apenas expõe as opções de log como contrato.
     */
    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()
            ->useLogName('task')
            ->logOnly(['user_id'])
            ->dontSubmitEmptyLogs();
    }
}