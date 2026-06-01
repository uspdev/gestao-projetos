<?php

namespace App\Models;

use App\Contracts\HasCommentRecipients;
use App\Enums\Meeting\MeetingStatus;

use App\Models\Project as ProjectModel;
use App\Models\Task as TaskModel;
use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use App\Morphs\DiscussableMap;

class Meeting extends Model implements HasCommentRecipients
{
    use HasFactory, SoftDeletes, Auditable;

    protected $fillable = [
        'title',
        'scheduled_at',
        'location',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'status' => MeetingStatus::class,
        ];
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

        $defaultType = array_key_exists($projectTypeKey, $discussableOptions)
            ? $projectTypeKey
            : (array_key_first($discussableOptions) ?: $projectTypeKey);

        $typeValue = old('discussable_type', $defaultType);
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
            'typeValue',
            'orderValue',
            'projectOptions',
            'taskOptions',
            'meetingProjects'
        );
    }
}
