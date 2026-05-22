<?php

namespace App\Models;

use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ProjectType extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    /**
     * Relacionamento com projetos 1-N
     */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    /**
     * Relacionamento com configurações de módulos 1-N
     */
    public function projectTypeModules(): HasMany
    {
        return $this->hasMany(ProjectTypeModule::class);
    }

    /**
     * Relacionamento com módulos N-N
     */
    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'project_type_modules')
            ->withPivot('enabled', 'required', 'editable', 'config')
            ->withTimestamps();
    }

    /**
     * Relacionamento com fases N-N
     */
    public function phases(): BelongsToMany
    {
        return $this->belongsToMany(Phase::class, 'project_type_phases')
            ->withPivot('order', 'is_initial', 'is_final')
            ->withTimestamps()
            ->orderBy('project_type_phases.order');
    }

    public function isModuleEnabled(string $slug): bool
    {
        $this->loadMissing('modules');

        $normalized = strtolower(trim($slug));

        return $this->modules->contains(function (Module $module) use ($normalized) {
            return $module->slug === $normalized && (bool) ($module->pivot?->enabled ?? false);
        });
    }

    public function setSlugAttribute($value): void
    {
        $this->attributes['slug'] = $value === null ? null : Str::slug((string) $value);
    }
}
