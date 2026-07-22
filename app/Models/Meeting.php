<?php

namespace App\Models;

use App\Contracts\HasCommentRecipients;
use App\Enums\Meeting\MeetingStatus;
use App\Morphs\Duplicable;

use App\Models\Project as ProjectModel;
use App\Models\Task as TaskModel;
use App\Traits\Auditable;
use App\Traits\InteractsWithFiles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use App\Morphs\DiscussableMap;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Models\Activity;
use InvalidArgumentException;
use Spatie\MediaLibrary\HasMedia;

class Meeting extends Model implements HasCommentRecipients, Duplicable, HasMedia
{
    use HasFactory, SoftDeletes, Auditable, LogsActivity, InteractsWithFiles;

    protected $fillable = [
        'title',
        'scheduled_at',
        'location',
        'notes',
        'ata',
        'transcription',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'status' => MeetingStatus::class,
        ];
    }

    protected $touches = ['projects'];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('meeting')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        if (! in_array($eventName, ['created', 'updated'], true) || ! $activity->properties) {
            return;
        }

        $properties = $activity->properties;

        foreach (['attributes', 'old'] as $changeSet) {
            $values = $properties->get($changeSet, []);

            if (! array_key_exists('transcription', $values)) {
                continue;
            }

            $transcription = $values['transcription'];
            $values['transcription_length'] = is_string($transcription)
                ? mb_strlen($transcription)
                : null;
            $values['transcription_sha256'] = is_string($transcription)
                ? hash('sha256', $transcription)
                : null;
            unset($values['transcription']);

            $properties->put($changeSet, $values);
        }

        $activity->properties = $properties;
    }

    /**
     * Relacionamento com projetos N-N
     */
    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'meeting_projects')
            ->using(MeetingProject::class);
    }

    /**
     * Projeto usado nas rotas da reunião para um usuário (primeiro vínculo acessível).
     */
    public function contextProjectFor(User $user, ?Collection $availableProjectIds = null): ?Project
    {
        $this->loadMissing('projects');

        $availableProjectIds ??= Project::availableForMeetings($user)->pluck('id');

        return $this->projects
            ->whereIn('id', $availableProjectIds)
            ->sortBy('name')
            ->first(fn(Project $project) => $user->isViewerOfProject($project));
    }

    /**
     * Relacionamento com comentarios (morph)
     */
    public function comments(): MorphMany
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    // Implementação do método da interface HasCommentRecipients
    // para obter os destinatários de comentários relacionados à reunião
    public function commentRecipients(): Collection
    {
        $this->loadMissing('projects.users');

        return $this->projects
            ->flatMap(fn($project) => $project->users)
            ->unique('id')
            ->values();
    }

    /**
     * Relacionamento com meeting items 1-N
     */
    public function meetingItems(): HasMany
    {
        return $this->hasMany(MeetingItem::class);
    }

    // Método para obter os projetos relacionados à reunião, incluindo os filhos, para uso na agenda
    public function projectsForAgenda(): EloquentCollection
    {
        $meetingProjects = $this->projects()
            ->select('projects.id', 'projects.name', 'projects.slug', 'projects.parent_id')
            ->with([
                'tasks' => function ($query) {
                    $query->select('tasks.id', 'tasks.project_id', 'tasks.title');
                },
                'children' => function ($query) {
                    $query->select('projects.id', 'projects.parent_id', 'projects.name', 'projects.slug');
                },
            ])
            ->get();

        // Garantir que projetos sem módulo de tarefas habilitado retornem uma coleção vazia de tarefas para evitar erros nas views
        $meetingProjects->each(function (ProjectModel $project) {
            if (! $project->isModuleEnabled('tasks')) {
                $project->setRelation('tasks', collect());
            }
        });

        return $meetingProjects;
    }

    // Método para obter os dados necessários para o formulário de itens de pauta
    // Varias trataivas de algumas views
    public function meetingItemFormData(?Collection $meetingItems = null): array
    {
        $meetingItems = $meetingItems ?? collect();
        $discussableOptions = DiscussableMap::options();
        $projectTypeKey = 'project';
        $taskTypeKey = 'task';
        $independentTypeKey = 'independent';

        $defaultType = array_key_exists($projectTypeKey, $discussableOptions)
            ? $projectTypeKey
            : (array_key_first($discussableOptions) ?: $projectTypeKey);

        $typeValue = old('item_type', $defaultType);
        $orderValue = old('order', (int) ($meetingItems->max('order') ?? 0) + 1);

        // Resolver as classes de projeto e tarefa para filtrar os itens de pauta existentes
        $projectClass = DiscussableMap::resolveClass($projectTypeKey) ?: ProjectModel::class;
        $taskClass = DiscussableMap::resolveClass($taskTypeKey) ?: TaskModel::class;

        // Obter os IDs dos projetos e tarefas já associados à reunião para evitar duplicatas nas opções
        $existingProjectIds = $meetingItems
            ->filter(function ($meetingItem) use ($projectClass) {
                $resolved = DiscussableMap::resolveClass((string) ($meetingItem->discussable_type ?? ''));
                return $resolved === $projectClass;
            })
            ->pluck('discussable_id')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $existingTaskIds = $meetingItems
            ->filter(function ($meetingItem) use ($taskClass) {
                $resolved = DiscussableMap::resolveClass((string) ($meetingItem->discussable_type ?? ''));
                return $resolved === $taskClass;
            })
            ->pluck('discussable_id')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $meetingProjects = $this->projectsForAgenda();

        // Gerar opções de projetos e tarefas, excluindo os já associados à reunião
        $projectOptions = $meetingProjects->flatMap(function ($meetingProject) use ($existingProjectIds) {
            $options = [];

            if (!in_array((int) $meetingProject->id, $existingProjectIds, true)) {
                $options[] = [
                    'value' => $meetingProject->id,
                    'label' => $meetingProject->name,
                ];
            }

            foreach ($meetingProject->children as $childProject) {
                if (in_array((int) $childProject->id, $existingProjectIds, true)) {
                    continue;
                }

                $options[] = [
                    'value' => $childProject->id,
                    'label' => $childProject->name . ' (subprojeto)',
                ];
            }

            return $options;
        })->values()->all();

        // Gerar opções de tarefas, excluindo os já associados à reunião
        $taskOptions = $meetingProjects->flatMap(function ($meetingProject) use ($existingTaskIds) {
            $options = [];

            foreach ($meetingProject->tasks as $task) {
                if (in_array((int) $task->id, $existingTaskIds, true)) {
                    continue;
                }

                $options[] = [
                    'value' => $task->id,
                    'label' => $meetingProject->name . ' - ' . $task->title,
                ];
            }

            return $options;
        })->values()->all();

        return compact(
            'discussableOptions',
            'projectTypeKey',
            'taskTypeKey',
            'independentTypeKey',
            'typeValue',
            'orderValue',
            'projectOptions',
            'taskOptions',
            'meetingProjects'
        );
    }
    /**
     * Cria uma cópia desta reunião para uma nova data e hora.
     *
     * @param array{
     *     scheduled_at: \DateTimeInterface|string,
     *     title?: string,
     *     project_ids?: array<int, int|string>
     * } $options Opções para a duplicação da reunião.
     *
     * @return Model A nova reunião criada.
     *
     * @throws InvalidArgumentException Quando a nova data e hora não é informada.
     */
    public function duplicate(array $options = []): Model
    {
        $scheduledAt = $options['scheduled_at'] ?? null;

        if (blank($scheduledAt)) {
            throw new InvalidArgumentException('Informe a nova data e hora da reunião.');
        }

        $this->loadMissing(['projects', 'meetingItems']);

        $projectIds = collect($options['project_ids'] ?? $this->projects->pluck('id')->all())
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $copy = self::create([
            'title' => $options['title'] ?? $this->title,
            'scheduled_at' => $scheduledAt,
            'location' => $this->location,
            'notes' => $this->notes,
            'ata' => $this->ata,
            'transcription' => $this->transcription,
            'status' => MeetingStatus::SCHEDULED->value,
        ]);

        $copy->projects()->sync($projectIds);

        foreach ($this->meetingItems->sortBy('order') as $item) {
            MeetingItem::create([
                'meeting_id' => $copy->id,
                'discussable_type' => $item->discussable_type,
                'discussable_id' => $item->discussable_id,
                'title' => $item->title,
                'order' => $item->order,
                'notes' => null,
            ]);
        }

        return $copy;
    }

    public function duplicationBlockReason(): ?string
    {
        return null;
    }
}
