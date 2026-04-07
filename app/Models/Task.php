<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

enum TaskLabel: string {
    case FIX = 'FIX';
    case FEATURE = 'FEATURE';
    case TEST = 'TEST';
    case DOC = 'DOC';
    case REFACTOR = 'REFACTOR';

    public function label(): string
    {
        return match($this) {
            self::FIX => 'Correção',
            self::FEATURE => 'Funcionalidade',
            self::TEST => 'Teste',
            self::DOC => 'Documentação',
            self::REFACTOR => 'Refatoração',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::FIX => 'badge-danger',
            self::FEATURE => 'badge-success',
            self::TEST => 'badge-primary',
            self::DOC => 'badge-info',
            self::REFACTOR => 'badge-warning',
        };
    }
}

enum TaskPriority: int
{
    case URGENT = 1;
    case HIGH = 2;
    case MEDIUM = 3;
    case LOW = 4;

    public function label(): string
    {
        return match($this) {
            self::URGENT => 'Urgente',
            self::HIGH => 'Alta',
            self::MEDIUM => 'Média',
            self::LOW => 'Baixa',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::URGENT => 'badge-danger',
            self::HIGH => 'badge-warning',
            self::MEDIUM => 'badge-info',
            self::LOW => 'badge-secondary',
        };
    }
}

enum TaskStatus: string {
    case TO_DO = 'TO_DO';
    case IN_PROGRESS = 'IN_PROGRESS';
    case IN_REVIEW = 'IN_REVIEW';
    case DONE = 'DONE';
    case HOLD = 'HOLD';

    public function label(): string
    {
        return match($this) {
            self::TO_DO => 'A Fazer',
            self::IN_PROGRESS => 'Em Andamento',
            self::IN_REVIEW => 'Em Revisão',
            self::DONE => 'Concluída',
            self::HOLD => 'Em Espera',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::TO_DO => 'badge-secondary',
            self::IN_PROGRESS => 'badge-primary',
            self::IN_REVIEW => 'badge-info',
            self::DONE => 'badge-success',
            self::HOLD => 'badge-warning',
        };
    }
}

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
            'status' => TaskStatus::class,
            'label' => TaskLabel::class,
            'start_date' => 'date',
            'due_date' => 'date',
            'priority' => TaskPriority::class
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
