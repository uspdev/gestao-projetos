<?php

namespace App\Models;

use App\Models\Module;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ProjectTypeModule extends Pivot
{
    use HasFactory;

    public $incrementing = true;
    public $timestamps = true;

    protected $table = 'project_type_modules';

    protected $fillable = [
        'project_type_id',
        'module_id',
        'enabled',
        'required',
        'editable',
        'config',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'required' => 'boolean',
            'editable' => 'boolean',
            'config' => 'array',
        ];
    }
    // =========== Relacionamentos =============

    // Um tipo de projeto pode ter várias configurações de módulos
    public function projectType(): BelongsTo
    {
        return $this->belongsTo('App\\Models\\ProjectType');
    }

    // Um módulo pode ter várias configurações por tipo de projeto
    public function module(): BelongsTo
    {
        return $this->belongsTo(Module::class);
    }

    public function getActivitylogOptions(): \Spatie\Activitylog\LogOptions
    {
        return \Spatie\Activitylog\LogOptions::defaults()
            ->useLogName('project_type')
            ->logOnly(['enabled', 'required', 'editable', 'config'])
            ->dontSubmitEmptyLogs();
    }
}
