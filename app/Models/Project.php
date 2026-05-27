<?php

namespace App\Models;

use App\Contracts\HasCommentRecipients;
use App\Enums\Project\ProjectPermissionInheritance;
use App\Enums\Project\ProjectStatus;
use App\Enums\Project\ProjectUserRole;
use App\Enums\Project\ProjectVisibility;
use App\Morphs\Discussable;
use App\Traits\Auditable;
use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Spatie\Tags\HasTags;

class Project extends Model implements Discussable, HasCommentRecipients
{
    use HasFactory, SoftDeletes, Auditable, HasTags, HasSlug;

    public const ORGANIZATIONAL_TYPE_SLUG = 'organizacional';

    protected $fillable = [
        'name',
        'slug',
        'status',
        'description',
        'project_type_id',
        'parent_id',
        'visibility',
        'permission_inheritance',
        'phase_id',
    ];

    protected array $roleByUserCache = [];
    protected string $slugSourceColumn = 'name';

    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'visibility' => ProjectVisibility::class,
            'permission_inheritance' => ProjectPermissionInheritance::class,
            'phase_id' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::created(function (Project $project) {
            $project->initializeProjectModules();
        });

        static::deleting(function (Project $project) {
            if ($project->isForceDeleting()) {
                $project->tasks()
                    ->withTrashed()
                    ->get()
                    ->each(fn(Task $task) => $task->forceDelete());

                return;
            }

            $project->tasks()->get()->each(function (Task $task) {
                $task->deleted_via_project = true;
                $task->saveQuietly();

                $task->delete();
            });
        });

        static::restoring(function (Project $project) {
            $project->tasks()
                ->withTrashed()
                ->where('deleted_via_project', true)
                ->get()
                ->each(function (Task $task) {
                    $task->deleted_via_project = false;
                    $task->saveQuietly();
                    $task->restore();
                });
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function initializeProjectModules(): void
    {
        if (!Schema::hasTable('project_modules') || !Schema::hasTable('modules')) {
            return;
        }

        if ($this->projectModules()->exists()) {
            return;
        }

        $now = now();
        $defaultsUsed = false;
        $projectTypeId = (int) ($this->project_type_id ?? 0);

        if (Schema::hasTable('project_type_modules') && $projectTypeId > 0) {
            $rows = DB::table('project_type_modules')
                ->where('project_type_id', $projectTypeId)
                ->get(['module_id', 'enabled']);

            if ($rows->isNotEmpty()) {
                foreach ($rows as $row) {
                    DB::table('project_modules')->updateOrInsert(
                        ['project_id' => $this->id, 'module_id' => $row->module_id],
                        ['enabled' => (bool) $row->enabled, 'created_at' => $now, 'updated_at' => $now]
                    );
                }

                $defaultsUsed = true;
            }
        }

        if (!$defaultsUsed) {
            $modules = DB::table('modules')->pluck('id', 'slug');

            foreach ($modules as $slug => $moduleId) {
                $enabled = ($slug === 'tasks');

                DB::table('project_modules')->updateOrInsert(
                    ['project_id' => $this->id, 'module_id' => $moduleId],
                    ['enabled' => $enabled, 'created_at' => $now, 'updated_at' => $now]
                );
            }
        }
    }

    /**
     * Relacionamento com users N-N
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(ProjectUser::class)
            ->withPivot('role', 'pinned')
            ->withTimestamps()
            ->orderBy('name');
    }

    /**
     * Relacionamento com tasks 1-N
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Relacionamento com reunioes N-N
     */
    public function meetings(): BelongsToMany
    {
        return $this->belongsToMany(Meeting::class, 'meeting_projects');
    }

    /**
     * Relacionamento com comentarios (morph)
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
    // Método para obter os destinatários de comentários relacionados ao projeto
    public function commentRecipients(): SupportCollection
    {
        $this->loadMissing('users');

        return $this->users->unique('id')->values();
    }

    /**
     * Relacionamento com meeting items via morph (Project pode ser um meeting item)
     */
    public function meetingItems(): MorphMany
    {
        return $this->morphMany(MeetingItem::class, 'discussable');
    }

    public function parentProjectId(): ?int
    {
        return $this->parent_id ?: $this->getKey();
    }

    /**
     * Relacionamento com tipo de projeto N-1
     */
    public function projectType(): BelongsTo
    {
        return $this->belongsTo(ProjectType::class);
    }

    // Retorna a configuração de um módulo específico para o projeto
    public function projectTypeModuleConfig(string $slug): ?array
    {
        $this->loadMissing('projectType.modules');

        return $this->projectType?->moduleConfig($slug);
    }

    // Retorna os slugs dos módulos habilitados para o projeto,
    // considerando as regras de herança do tipo de projeto e os overrides específicos do projeto,
    // para facilitar a verificação de disponibilidade de funcionalidades em diferentes partes da aplicação sem a necessidade
    // de carregar toda a relação de módulos.
    public function allowedModuleSlugs(): array
    {
        $this->loadMissing('projectType.modules');

        return $this->projectType?->allowedModuleSlugs() ?? [];
    }

    public function activeModuleSlugs(): array
    {
        $this->loadMissing('modules');

        return $this->modules
            ->filter(fn(Module $module) => (bool) ($module->pivot?->enabled ?? false))
            ->pluck('slug')
            ->values()
            ->all();
    }

    public function activeModulesSummary(): array
    {
        $this->loadMissing('modules');

        return $this->modules
            ->filter(fn(Module $module) => (bool) ($module->pivot?->enabled ?? false))
            ->map(fn(Module $module) => [
                'slug' => $module->slug,
                'name' => $module->name,
                'description' => $module->description,
                'enabled' => true,
            ])
            ->values()
            ->all();
    }

    /**
     * Relacionamento com fase N-1
     */
    public function phase(): BelongsTo
    {
        return $this->belongsTo(Phase::class);
    }

    /**
     * Relacionamento com projeto pai N-1
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'parent_id');
    }

    /**
     * Relacionamento com projetos filhos 1-N
     */
    public function children(): HasMany
    {
        return $this->hasMany(Project::class, 'parent_id');
    }

    public function isSubproject(): bool
    {
        return ! is_null($this->parent_id);
    }

    // Verifica se o projeto é do tipo organizacional
    public function isOrganizational(): bool
    {
        $this->loadMissing('projectType:id,slug');

        return $this->projectType?->slug === self::ORGANIZATIONAL_TYPE_SLUG;
    }

    public function isRootProject(): bool
    {
        return is_null($this->parent_id);
    }

    public function hasSubprojects(): bool
    {
        return $this->relationLoaded('children')
            ? $this->children->isNotEmpty()
            : $this->children()->exists();
    }
    public function adminIds(): SupportCollection
    {
        if ($this->relationLoaded('users')) {
            return $this->users
                ->filter(fn(User $user) => $this->userRole($user) === ProjectUserRole::ADMIN)
                ->pluck('id')
                ->values();
        }

        return $this->users()
            ->wherePivot('role', ProjectUserRole::ADMIN->value)
            ->pluck('users.id')
            ->values();
    }

    public function sharesAnyAdmin(Project $other): bool
    {
        $adminIds = $this->adminIds();
        $otherAdminIds = $other->adminIds();

        return $adminIds->intersect($otherAdminIds)->isNotEmpty();
    }

    /**
     * Verifica se o projeto pode ser vinculado como subprojeto do projeto pai, retornando a razão caso não possa
     */
    public function subprojectLinkBlockReason(Project $parent): ?string
    {
        if ($this->getKey() === $parent->getKey()) {
            return 'Selecione um projeto diferente do projeto atual.';
        }

        if (! $this->isRootProject()) {
            return 'O projeto selecionado ja e um subprojeto.';
        }

        if ($this->isOrganizational() && $parent->isOrganizational()) {
            return 'Projetos organizacionais não podem ser subprojetos de outros projetos organizacionais.';
        }

        if ($this->hasSubprojects()) {
            return 'O projeto selecionado possui subprojetos e nao pode ser vinculado.';
        }

        if (! $this->sharesAnyAdmin($parent)) {
            return 'O projeto selecionado possui administrador diferente do projeto pai.';
        }

        return null;
    }

    public function projectModules(): HasMany
    {
        return $this->hasMany(ProjectModule::class);
    }

    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'project_modules')
            ->withPivot('enabled')
            ->withTimestamps()
            ->orderBy('name');
    }

    public function isModuleEnabled(string $moduleSlug): bool
    {
        return Module::isEnabledForProject($this, $moduleSlug);
    }

    public function tasksTagsIds(?Collection $tasks = null): array
    {
        $tasks = $tasks ?? ($this->relationLoaded('tasks') ? $this->tasks : $this->tasks()->get());
        $tasks->loadMissing('tags');

        return $tasks->mapWithKeys(function (Task $task) {
            $tagIds = $task->tags
                ->where('type', Tag::TYPE_TASK)
                ->pluck('id')
                ->all();

            return [$task->id => $tagIds];
        })->all();
    }

    public function tagsIds(): array
    {
        $tags = $this->relationLoaded('tags')
            ? $this->tags->where('type', Tag::TYPE_PROJECT)
            : $this->tagsWithType(Tag::TYPE_PROJECT);

        return $tags
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->all();
    }

    public function scopeAccessibleBy(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->whereHas('users', function (Builder $q) use ($user) {
            $q->where('users.id', $user->id);
        });
    }

    public function scopeAvailableForMeetings(Builder $query, User $user): Builder
    {
        return $query
            ->accessibleBy($user)
            ->whereHas('modules', function (Builder $q) {
                $q->where('modules.slug', 'meetings')
                    ->where('project_modules.enabled', true);
            });
    }

    public function setSlugAttribute($value): void
    {
        $this->attributes['slug'] = $value === null ? null : Str::slug((string) $value);
    }

    public function userRole(?User $user): ?ProjectUserRole
    {
        if (!$user) {
            return null;
        }

        if (array_key_exists($user->id, $this->roleByUserCache)) {
            return $this->roleByUserCache[$user->id];
        }

        if (isset($this->pivot) && (int) ($this->pivot->user_id ?? 0) === (int) $user->id) {
            return $this->roleByUserCache[$user->id] = $this->parseProjectUserRole($this->pivot->role ?? null);
        }

        if ($this->relationLoaded('users')) {
            $member = $this->users->firstWhere('id', $user->id);
            return $this->roleByUserCache[$user->id] = $this->parseProjectUserRole($member?->pivot?->role ?? null);
        }

        $member = $this->users()->where('users.id', $user->id)->first();

        return $this->roleByUserCache[$user->id] = $this->parseProjectUserRole($member?->pivot?->role ?? null);
    }

    public function isPinnedBy(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        if (isset($this->pivot) && (int) ($this->pivot->user_id ?? 0) === (int) $user->id) {
            return (bool) ($this->pivot->pinned ?? false);
        }

        if ($this->relationLoaded('users')) {
            $member = $this->users->firstWhere('id', $user->id);

            return (bool) ($member?->pivot?->pinned ?? false);
        }

        $member = $this->users()->where('users.id', $user->id)->first();

        return (bool) ($member?->pivot?->pinned ?? false);
    }

    public function isLastAdmin(User $user): bool
    {
        if ($this->userRole($user) !== ProjectUserRole::ADMIN) {
            return false;
        }

        $adminsCount = $this->relationLoaded('users')
            ? $this->users->filter(function (User $member) {
                return $this->userRole($member) === ProjectUserRole::ADMIN;
            })->count()
            : $this->users()->wherePivot('role', ProjectUserRole::ADMIN->value)->count();

        return $adminsCount <= 1;
    }
    public function isAdminInParent(User $user): bool
    {
        if (! $this->isSubproject()) {
            return false;
        }

        $parent = $this->parent;

        if (! $parent) {
            return false;
        }

        return $parent->userRole($user) === ProjectUserRole::ADMIN;
    }

    public function syncTagsByIds(?array $tagIds): void
    {
        if ($tagIds === null) {
            return;
        }

        $tagsToSync = Tag::whereIn('id', $tagIds)->get();
        $this->syncTagsWithType($tagsToSync, Tag::TYPE_PROJECT);
    }

    public function getIncompleteTasksCount(): int
    {
        return $this->tasks()
            ->where('status', '!=', \App\Enums\Task\TaskStatus::DONE)
            ->count();
    }

    public function getIncompleteMeetingsCount(): int
    {
        return $this->meetings()
            ->where('status', '!=', \App\Enums\Meeting\MeetingStatus::COMPLETED)
            ->count();
    }

    private function parseProjectUserRole(mixed $roleValue): ?ProjectUserRole
    {
        if ($roleValue instanceof ProjectUserRole) {
            return $roleValue;
        }

        if (!$roleValue) {
            return null;
        }

        return ProjectUserRole::tryFrom((string) $roleValue);
    }

    /**
     * Retorna projetos elegíveis para vincular como subprojetos,
     *
     * que são projetos raiz sem subprojetos e com pelo menos um admin em comum
     */
    public function linkableSubprojects()
    {
        $user = Auth::user();
        return Project::query()
            ->whereNull('parent_id')
            ->whereKeyNot($this->getKey())
            ->whereHas('projectType', function ($q) {
                $q->where('slug', '!=', Project::ORGANIZATIONAL_TYPE_SLUG);
            })
            // children gera uma subconsulta para verificar a existência de subprojetos
            ->doesntHave('children')
            // A verificação de admin em comum é feita com whereHas na relação de usuários,
            // filtrando por projetos que compartilhem pelo menos um admin com o projeto atual
            ->whereHas('users', function ($q) use ($user) {
                $q->where('users.id', $user->id)
                    ->where('project_user.role', ProjectUserRole::ADMIN->value);
            })
            // Carrega os tipos de projeto e os admins para exibição nos resultados, facilitando a identificação dos projetos candidatos
            ->with([
                'projectType',
                'users' => function ($q) {
                    $q->where('project_user.role', ProjectUserRole::ADMIN->value)
                        ->orderBy('project_user.created_at');
                },
            ])
            ->orderBy('name')
            ->get();
    }
}
