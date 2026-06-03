<?php

namespace App\Models;

use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;
use App\Traits\ResolvesAuditOwner;

class ProjectModule extends Pivot
{
    use HasFactory, ResolvesAuditOwner;

    public $incrementing = true;
    public $timestamps = true;

    protected $table = 'project_modules';

    protected $fillable = [
        'project_id',
        'module_id',
        'enabled',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
        ];
    }

    protected $touches = ['project'];

    protected static function booted(): void
    {
        static::created(function (ProjectModule $pivot) {
            activity()
                ->useLog('project')
                ->event('updated') 
                ->performedOn(static::resolveOwner(Project::class, $pivot->project_id))
                ->withProperties([
                    'attributes' => [
                        'module_id' => $pivot->module_id,
                        'enabled' => $pivot->enabled,
                    ],
                ])
                ->log('updated');
        });

        static::updated(function (ProjectModule $pivot) {
            if (!$pivot->isDirty('enabled')) {
                return;
            }

            activity()
                ->useLog('project')
                ->event('updated')
                ->performedOn(static::resolveOwner(Project::class, $pivot->project_id))
                ->withProperties([
                    'attributes' => [
                        'module_id' => $pivot->module_id,
                        'enabled' => $pivot->enabled,
                    ],
                    'old' => [
                        'module_id' => $pivot->module_id,
                        'enabled' => $pivot->getOriginal('enabled'),
                    ],
                ])
                ->log('updated');
        });
    }

    // =========== Relacionamentos =============

    // Um projeto pode ter várias configurações de módulos
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    // Um módulo pode ter várias configurações por projeto
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()
            ->useLogName('project')
            ->logOnly(['enabled'])
            ->dontSubmitEmptyLogs();
    }
}
