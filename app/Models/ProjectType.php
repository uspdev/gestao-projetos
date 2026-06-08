<?php

namespace App\Models;

use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;

class ProjectType extends Model
{
    use HasFactory, HasSlug, LogsActivity;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('project_type')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

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
            ->using(ProjectTypeModule::class)
            ->withPivot('enabled', 'required', 'editable', 'config')
            ->withTimestamps();
    }

    public function enabledModules()
    {
        return $this->modules
            ->filter(fn($module) => (bool) ($module->pivot?->enabled ?? false))
            ->values();
    }

    /**
     * Relacionamento com fases N-N
     */
    public function phases(): BelongsToMany
    {
        return $this->belongsToMany(Phase::class, 'project_type_phases')
            ->using(ProjectTypePhase::class)
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

    /**
     * Retorna a configuração resolvida de um módulo no projeto.
     *
     * Recupera o estado do módulo considerando o relacionamento
     * entre projeto e módulos, incluindo os valores definidos
     * no pivot (enabled, required e editable), sem a necessidade
     * de percorrer manualmente toda a coleção de módulos.
     *
     * Útil para verificações rápidas de disponibilidade de funcionalidades
     * e para telas de configuração do projeto, onde é necessário exibir
     * ou validar o estado dos módulos de forma eficiente.
     *
     * @param string $slug Identificador único do módulo.
     *
     * @return array{
     *     module: \App\Models\Module,
     *     enabled: bool,
     *     required: bool,
     *     editable: bool
     * }|null
     */
    public function moduleConfig(string $slug): ?array
    {
        $normalized = strtolower(trim($slug));
        if ($normalized === '') {
            return null;
        }

        $this->loadMissing('modules');

        $module = $this->modules->firstWhere('slug', $normalized);
        if (! $module) {
            return null;
        }

        return [
            'module' => $module,
            'enabled' => (bool) ($module->pivot?->enabled ?? false),
            'required' => (bool) ($module->pivot?->required ?? false),
            'editable' => (bool) ($module->pivot?->editable ?? true),
        ];
    }

    // Retorna os slugs dos módulos habilitados para o projeto
    public function allowedModuleSlugs(): array
    {
        $this->loadMissing('modules');

        return $this->modules->pluck('slug')->all();
    }

    public function setSlugAttribute($value): void
    {
        $this->attributes['slug'] = $value === null ? null : Str::slug((string) $value);
    }
}
