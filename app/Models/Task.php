<?php

namespace App\Models;

use App\Contracts\HasCommentRecipients;
use App\Morphs\Duplicable;
use App\Morphs\Discussable;
use App\Enums\Task\TaskPriority;
use App\Enums\Task\TaskStatus;
use App\Models\Tag;
use App\Traits\Auditable;
use App\Traits\InteractsWithFiles;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Spatie\Tags\HasTags;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\MediaLibrary\HasMedia;

class Task extends Model implements Discussable, HasCommentRecipients, Duplicable, HasMedia
{
    use HasFactory, SoftDeletes, Auditable, HasTags, LogsActivity, InteractsWithFiles;

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

    protected $touches = ['project'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('task')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->using(TaskUser::class)
            ->withTimestamps();
    }

    /**
     * Relacionamento com comentarios (morph)
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
    // Implementação do método da interface HasCommentRecipients
    // para obter os destinatários de comentários relacionados à tarefa
    public function commentRecipients(): Collection
    {
        $this->loadMissing('users');

        return $this->users->unique('id')->values();
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

    public function scopeWithEnabledProjectModule(Builder $query, string $slug): Builder
    {
        $normalized = strtolower(trim($slug));
        if ($normalized === '') {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereHas('project.modules', function (Builder $moduleQuery) use ($normalized) {
            $moduleQuery
                ->where('modules.slug', $normalized)
                ->where('project_modules.enabled', true);
        });
    }

    // escopo específico para o módulo de tarefas, para facilitar a reutilização em outros lugares do código
    public function scopeWithTasksModuleEnabled(Builder $query): Builder
    {
        return $query->withEnabledProjectModule('tasks');
    }

    /**
     * Gera um texto concatenado com os campos utilizados em busca JS
     */
    public function searchableText(): string
    {
        return strtolower(
            implode(' ', array_filter([
                $this->title,
                $this->project?->name,
                $this->priority?->label(),
                $this->users->pluck('name')->implode(' '),
            ]))
        );
    }
    /**
     * Cria uma cópia desta tarefa.
     *
     * @param array{
     *     project_id?: int|string,
     *     title?: string,
     *     start_date?: \DateTimeInterface|string|null,
     *     due_date?: \DateTimeInterface|string|null,
     *     copy_assignees?: bool,
     *     preserve_status?: bool
     * } $options Opções para a duplicação da tarefa.
     *
     * @return Model A nova tarefa criada.
     */
    public function duplicate(array $options = []): Model
    {
        $this->loadMissing(['tags', 'users']);

        $copyAssignees = (bool) ($options['copy_assignees'] ?? true);
        $assigneeIds = $copyAssignees
            ? $this->users->pluck('id')->map(fn($id) => (int) $id)->all()
            : [];

        $copy = self::create([
            'project_id' => (int) ($options['project_id'] ?? $this->project_id),
            'title' => $options['title'] ?? $this->title,
            'description' => $this->description,
            'priority' => $this->priority?->value,
            'status' => (bool) ($options['preserve_status'] ?? false)
                ? $this->status?->value
                : (empty($assigneeIds) ? TaskStatus::NEW->value : TaskStatus::ASSIGNED->value),
            'start_date' => array_key_exists('start_date', $options)
                ? $options['start_date']
                : $this->start_date,
            'due_date' => array_key_exists('due_date', $options)
                ? $options['due_date']
                : $this->due_date,
            'completed_at' => null,
        ]);

        $copy->syncTagsWithType(
            $this->tags->where('type', Tag::TYPE_TASK),
            Tag::TYPE_TASK
        );
        $copy->users()->sync($assigneeIds);

        return $copy;
    }

    public function duplicationBlockReason(): ?string
    {
        return null;
    }
}
