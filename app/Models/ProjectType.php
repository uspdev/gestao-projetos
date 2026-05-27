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
        'enabled',
    ];

    protected $casts = [
        'enabled' => 'boolean',
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
            ->using(ProjectTypeModule::class)
            ->withPivot('enabled', 'required', 'editable', 'config')
            ->withTimestamps();
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
    // Retorna a configuração de um módulo específico para o projeto,
    // considerando as regras de herança do tipo de projeto e os overrides específicos do projeto,
    // para facilitar a verificação de disponibilidade de funcionalidades em diferentes partes da aplicação
    // sem a necessidade de carregar toda a relação de módulos.
    // Fiz dessa forma sem a necessidade de carregar toda a relação de módulos para otimizar o desempenho e
    // deixar mais fácil a verificação
    // como na tela de configurações do projeto, onde exibimos uma lista de módulos com seus status habilitado/desabilitado,
    // obrigatoriedade e editabilidade.
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
