<?php

namespace App\Models;

use App\Enums\Task\TaskPriority;
use App\Enums\Task\TaskStatus;
use App\Models\Tag;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Tags\HasTags;

class Task extends Model
{
    use HasFactory, SoftDeletes, Auditable, HasTags;

    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
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
        'start_date',
        'due_date',
    ];

    public function availableTags()
    {
        return Tag::withType('tasks')
            ->select('id', 'name', 'color', 'description')
            ->orderBy('name')
            ->get();
    }

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
