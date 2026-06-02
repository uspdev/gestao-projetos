<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ProjectTypePhase extends Pivot
{
    public $incrementing = true;
    public $timestamps = true;

    protected $table = 'project_type_phases';

    protected $casts = [
        'order' => 'integer',
        'is_initial' => 'boolean',
        'is_final' => 'boolean',
    ];

    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()
            ->useLogName('project_type')
            ->logOnly(['order', 'is_initial', 'is_final'])
            ->dontSubmitEmptyLogs();
    }
}
