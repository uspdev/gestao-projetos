<?php

namespace App\Models;

use App\Morphs\Discussable;
use App\Enums\Project\ProjectPermissionInheritance;
use App\Enums\Project\ProjectPhase;
use App\Enums\Project\ProjectStatus;
use App\Enums\Project\ProjectUserRole;
use App\Enums\Project\ProjectVisibility;
use App\Traits\HasSlug;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Tags\HasTags;

class Project extends Model implements Discussable
{
    use HasFactory, SoftDeletes, Auditable, HasTags, HasSlug;

    protected $fillable = [
        'name',
        'slug',
        'status',
        'description',
        'project_type_id',
        'parent_id',
        'visibility',
        'permission_inheritance',
        'phase',
    ];

    protected array $roleByUserCache = [];
    protected string $slugSourceColumn = 'name';

    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
            'visibility' => ProjectVisibility::class,
            'permission_inheritance' => ProjectPermissionInheritance::class,
            'phase' => ProjectPhase::class,
        ];
    }

    protected static function booted(): void
    {
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

    /**
     * Relacionamento com users N-N
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(ProjectUser::class)
            ->withPivot('role', 'pinned')
            ->withTimestamps();
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

    public function projectModules(): HasMany
    {
        return $this->hasMany(ProjectModule::class);
    }

    public function modules(): BelongsToMany
    {
        return $this->belongsToMany(Module::class, 'project_modules')
            ->withPivot('enabled')
            ->withTimestamps();
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
}
