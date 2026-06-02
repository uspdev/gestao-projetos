<?php

namespace App\Models;

use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class Phase extends Model
{
    use HasFactory, HasSlug, LogsActivity;

    protected $fillable = [
        'name',
        'slug',
        'color',
        'description',
        'is_active',
        'is_initial',
        'is_final',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_initial' => 'boolean',
            'is_final' => 'boolean',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('phase')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    // Relacionamento com projetos 1-N
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function projectTypes(): BelongsToMany
    {
        return $this->belongsToMany(ProjectType::class, 'project_type_phases')
            ->using(ProjectTypePhase::class)
            ->withPivot('order', 'is_initial', 'is_final')
            ->withTimestamps();
    }
}
