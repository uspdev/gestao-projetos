<?php

namespace App\Models;

use App\Enums\Project\ProjectStatus;
use App\Enums\Project\ProjectUserRole;
use App\Traits\HasSlug;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Tags\HasTags;

class Project extends Model
{
    use HasFactory, SoftDeletes, Auditable, HasTags, HasSlug;

    protected array $roleByUserCache = [];
    protected string $slugSourceColumn = 'name';

    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
        ];
    }

    protected $fillable = [
        'name',
        'slug',
        'status',
        'description',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
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

    public function isLastOwner(User $user): bool
    {
        if ($this->userRole($user) !== ProjectUserRole::OWNER) {
            return false;
        }

        $ownersCount = $this->relationLoaded('users')
            ? $this->users->filter(function (User $member) {
                return $this->userRole($member) === ProjectUserRole::OWNER;
            })->count()
            : $this->users()->wherePivot('role', ProjectUserRole::OWNER->value)->count();

        return $ownersCount <= 1;
    }

    public function syncTagsByIds(?array $tagIds): void
    {
        if ($tagIds === null) {
            return;
        }

        $tagsToSync = Tag::whereIn('id', $tagIds)->get();
        $this->syncTagsWithType($tagsToSync, Tag::TYPE_PROJECT);
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

    public function scopeAccessibleBy(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->whereHas('users', function (Builder $q) use ($user) {
            $q->where('users.id', $user->id);
        });
    }

    /**
     * Relacionamento com users N-N
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(ProjectUser::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Relacionamento com tasks 1-N
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }
}
