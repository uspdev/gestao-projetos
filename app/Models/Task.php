<?php

namespace App\Models;

use App\Contracts\Discussable;
use App\Enums\Task\TaskPriority;
use App\Enums\Task\TaskStatus;
use App\Models\Tag;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Tags\HasTags;

class Task extends Model implements Discussable
{
    use HasFactory, SoftDeletes, Auditable, HasTags;

    protected $fillable = [
        'project_id',
        'title',
        'description',
        'priority',
        'status',
        'start_date',
        'due_date',
        'completed_at'
    ];

    protected function casts(): array
    {
        return [
            'status' => TaskStatus::class,
            'start_date' => 'date',
            'due_date' => 'date',
            'completed_at' => 'datetime',
            'priority' => TaskPriority::class
        ];
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

    /**
     * Relacionamento com comentarios (morph)
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    /**
     * Relacionamento com meeting items via morph (Task pode ser um meeting item)
     */
    public function meetingItems(): MorphMany
    {
        return $this->morphMany(MeetingItem::class, 'discussable');
    }

    public function parentProjectId(): ?int
    {
        return $this->project_id ?: null;
    }

    public function availableTags()
    {
        return Tag::forTasks();
    }

    public function syncTagsByIds(?array $tagIds): void
    {
        if ($tagIds === null) {
            return;
        }

        $tagsToSync = Tag::whereIn('id', $tagIds)->get();
        $this->syncTagsWithType($tagsToSync, Tag::TYPE_TASK);
    }

    public function isOverdue(): bool
    {
        return $this->due_date?->isPast()
            && !($this->status === \App\Enums\Task\TaskStatus::DONE);
    }

    public function isLocked(): bool
    {
        return $this->status === \App\Enums\Task\TaskStatus::DONE;
    }
}
