<?php

namespace App\Models;

use App\Models\ProjectModule;
use App\Models\ProjectTypeModule;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Schema;

class Module extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
    ];

    // =========== Relacionamentos =============

    // Um módulo pode ter várias configurações por tipo de projeto
    public function projectTypeModules(): HasMany
    {
        return $this->hasMany(ProjectTypeModule::class);
    }

    // Um módulo pode ter várias configurações por projeto
    public function projectModules(): HasMany
    {
        return $this->hasMany(ProjectModule::class);
    }

    // Um módulo pode estar associado a muitos projetos através de project_modules
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_modules')
            ->using(ProjectModule::class)
            ->withPivot('enabled')
            ->withTimestamps();
    }

    // ============== Métodos de resolução ==============

    // Verifica se um módulo está habilitado para um projeto específico, considerando as regras de herança e overrides
    public static function isEnabledForProject(Project $project, string $moduleSlug): bool
    {
        $moduleSlug = strtolower(trim($moduleSlug));

        if ($moduleSlug === '') {
            return false;
        }

        // Verificar se há um override específico para o projeto
        // override do projeto tem prioridade sobre o tipo de projeto,
        // ou seja, se o tipo de projeto tiver o módulo habilitado,
        // mas o projeto tiver um override desabilitando, o módulo deve ser considerado desabilitado para aquele projeto
        $projectOverride = self::resolveProjectOverride($project->id, $moduleSlug);
        // Se o módulo for obrigatório para o tipo de projeto,
        // ele deve ser considerado habilitado mesmo que haja um override desabilitando,
        // para garantir que as regras de obrigatoriedade sejam respeitadas
        $projectTypeDefault = self::resolveProjectTypeDefault($project, $moduleSlug);

        if ($projectOverride !== null) {
            if (($projectTypeDefault['required'] ?? false) === true && $projectOverride === false) {
                return true;
            }

            return $projectOverride;
        }

        if ($projectTypeDefault !== null) {
            return (bool) $projectTypeDefault['enabled'];
        }

        return false;
    }

    // Resolve a lista de módulos para um projeto, considerando as configurações específicas do projeto
    // e do tipo de projeto, e garantindo que todos os módulos registrados no banco de dados sejam considerados
    public static function resolveForProject(Project $project): array
    {
        $dbModules = self::resolveRegisteredDbModules();
        $allSlugs = collect($dbModules)->unique()->values();
        // Filtrar os módulos de acordo com o do tipo do projeto
        $allowedModuleSlugs = $project->allowedModuleSlugs();

        if (! empty($allowedModuleSlugs)) {
            $allSlugs = $allSlugs->filter(fn(string $slug) => in_array($slug, $allowedModuleSlugs, true))->values();
        }

        return $allSlugs
            ->map(function (string $slug) use ($project): array {
                $projectOverride = self::resolveProjectOverride($project->id, $slug);
                $projectTypeDefault = self::resolveProjectTypeDefault($project, $slug);

                $enabled = self::isEnabledForProject($project, $slug);

                $source = 'database';
                if ($projectTypeDefault !== null) {
                    $source = 'project_type_modules';
                }
                if ($projectOverride !== null) {
                    $source = 'project_modules';
                }

                $module = self::query()->where('slug', $slug)->first(['name', 'description']);

                return [
                    'slug' => $slug,
                    'name' => (string) ($module?->name ?? ucfirst(str_replace('_', ' ', $slug))),
                    'description' => $module?->description,
                    'enabled' => $enabled,
                    'source' => $source,
                    'required' => (bool) ($projectTypeDefault['required'] ?? false),
                    'editable' => (bool) ($projectTypeDefault['editable'] ?? true),
                ];
            })
            ->sortBy('name')
            ->values()
            ->all();
    }

    // ============== Métodos auxiliares ==============

    // Resolve a lista de slugs dos módulos registrados no banco de dados,
    // garantindo que o sistema funcione mesmo que a tabela de módulos não exista ou esteja vazia
    private static function resolveRegisteredDbModules(): array
    {
        if (!Schema::hasTable('modules')) {
            return [];
        }

        return self::query()->pluck('slug')->all();
    }

    // Resolve a configuração de um módulo para um projeto específico, considerando overrides
    // overrides -> seria a configuração específica do projeto, que tem prioridade sobre o tipo de projeto
    private static function resolveProjectOverride(int $projectId, string $moduleSlug): ?bool
    {
        if (!Schema::hasTable('project_modules') || !Schema::hasTable('modules')) {
            return null;
        }

        return ProjectModule::query()
            ->where('project_id', $projectId)
            ->whereHas('module', fn($query) => $query->where('slug', $moduleSlug))
            ->value('enabled');
    }

    // Resolve a configuração padrão de um módulo para um projeto específico, considerando o tipo de projeto
    // tipo de projeto -> seria a configuração padrão para um tipo de projeto, que é herdada
    // por todos os projetos desse tipo, a menos que haja um override específico do projeto
    private static function resolveProjectTypeDefault(Project $project, string $moduleSlug): ?array
    {
        if (!Schema::hasTable('project_type_modules') || !Schema::hasTable('modules')) {
            return null;
        }

        if (!Schema::hasColumn('projects', 'project_type_id')) {
            return null;
        }

        $projectTypeId = (int) ($project->project_type_id ?? 0);
        if ($projectTypeId <= 0) {
            return null;
        }

        $config = $project->projectTypeModuleConfig($moduleSlug);

        if (! $config) {
            return null;
        }

        return [
            'enabled' => (bool) ($config['enabled'] ?? false),
            'required' => (bool) ($config['required'] ?? false),
            'editable' => (bool) ($config['editable'] ?? true),
        ];
    }
}
