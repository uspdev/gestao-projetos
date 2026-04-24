<?php

namespace App\Models;

use App\Enums\Project\ProjectStatus;
use App\Enums\Project\ProjectUserRole;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Tags\HasTags;

/**
 * @property int $id
 * @property string $name
 * @property ProjectStatus $status
 * @property string|null $description
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Task> $tasks
 * @property-read int|null $tasks_count
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Database\Factories\ProjectFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereDeletedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Project withoutTrashed()
 * @method static Builder<static>|Project accessibleBy(\App\Models\User $user)
 * @property-read \App\Models\ProjectUser|null $pivot
 * @property \Illuminate\Database\Eloquent\Collection<int, \App\Models\Tag> $tags
 * @property-read int|null $tags_count
 * @method static Builder<static>|Project withAllTags(\ArrayAccess|\Spatie\Tags\Tag|array|string $tags, ?string $type = null)
 * @method static Builder<static>|Project withAllTagsOfAnyType($tags)
 * @method static Builder<static>|Project withAnyTags(\ArrayAccess|\Spatie\Tags\Tag|array|string $tags, ?string $type = null)
 * @method static Builder<static>|Project withAnyTagsOfAnyType($tags)
 * @method static Builder<static>|Project withAnyTagsOfType(array|string $type)
 * @method static Builder<static>|Project withoutTags(\ArrayAccess|\Spatie\Tags\Tag|array|string $tags, ?string $type = null)
 * @mixin \Eloquent
 */
class Project extends Model
{
    use HasFactory, SoftDeletes, Auditable, HasTags;

    protected array $roleByUserCache = [];

    protected function casts(): array
    {
        return [
            'status' => ProjectStatus::class,
        ];
    }

    protected $fillable = [
        'name',
        'status',
        'description',
    ];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
                    ->using(ProjectUser::class)
                    ->withPivot('role')
                    ->withTimestamps();
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
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
}
