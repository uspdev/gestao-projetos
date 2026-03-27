<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $project_id
 * @property string $title
 * @property string|null $description
 * @property string|null $priority
 * @property string $status
 * @property string|null $label
 * @property \Illuminate\Support\Carbon|null $start_date
 * @property \Illuminate\Support\Carbon|null $due_date
 * @property int|null $created_by
 * @property int|null $updated_by
 * @property int|null $deleted_by
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read \App\Models\Project|null $project
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\User> $users
 * @property-read int|null $users_count
 * @method static \Database\Factories\TaskFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereCreatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereDeletedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereDueDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereProjectId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereStartDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task whereUpdatedBy($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Task withoutTrashed()
 * @mixin \Eloquent
 */
class Task extends Model
{
    use HasFactory, SoftDeletes, Auditable;

    protected function casts(): array
    {
        return [
            'status' => 'string',
            'label' => 'string',
            'start_date' => 'date',
            'due_date' => 'date',
        ];
    }

    protected $fillable = [
        'project_id',
        'title',
        'description',
        'priority',
        'status',
        'label',
        'start_date',
        'due_date',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
                    ->withTimestamps();
    }
}
