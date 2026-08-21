<?php

namespace App\Services\Mentions;

use App\Enums\Task\TaskStatus;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

final class TaskMentionAdapter
{
    public const ALIAS = 'task';

    private MentionProjectContextResolver $contextResolver;
    private MentionContextualSearch $contextualSearch;

    public function __construct(
        ?MentionProjectContextResolver $contextResolver = null,
        ?MentionContextualSearch $contextualSearch = null,
    )
    {
        $this->contextResolver = $contextResolver ?? new MentionProjectContextResolver();
        $this->contextualSearch = $contextualSearch ?? new MentionContextualSearch();
    }

    public function supports(string $type): bool
    {
        return $type === self::ALIAS;
    }

    public function exists(string $key): bool
    {
        return preg_match('/^[1-9][0-9]*$/', $key) === 1
            && Task::query()->whereKey($key)->exists();
    }

    public function historicalExists(string $key): bool
    {
        return preg_match('/^[1-9][0-9]*$/', $key) === 1
            && Task::withTrashed()->whereKey($key)->exists();
    }

    public function isEligible(Model $source, string $key, ?User $reader = null): bool
    {
        $task = $this->resolve($key);

        if (! $task || ($source instanceof Task && $source->is($task))) {
            return false;
        }

        return $reader !== null
            && Gate::forUser($reader)->allows('view', $task);
    }

    /**
     * @return Collection<int, array{id: int, name: string, type: string, type_label: string, group: string, scope: 'contextual'|'global', completed: bool}>
     */
    public function search(Model $source, string $term = '', ?User $reader = null): Collection
    {
        if (! $reader || ! Schema::hasTable('tasks')) {
            return collect();
        }

        $term = trim($term);
        $contextTaskIds = $this->contextualTaskIds($source);
        $contextIds = $contextTaskIds ?? $this->contextProjectIds($source);

        if ($term === '' && $contextIds->isEmpty()) {
            return collect();
        }

        $tasks = Task::query()
            ->with('project')
            ->when($term !== '', fn ($query) => $query->where(
                'title',
                'like',
                '%' . $term . '%'
            ))
            ->when($term === '', fn ($query) => $query->whereIn(
                $contextTaskIds !== null ? 'id' : 'project_id',
                $contextIds->all(),
            ))
            ->orderBy('title')
            ->get(['id', 'project_id', 'title', 'status']);
        $excludedIds = $source instanceof Task
            ? collect([(int) $source->getKey()])
            : collect();

        $visible = $tasks
            ->reject(fn (Task $task): bool => $excludedIds->contains((int) $task->getKey()))
            ->filter(fn (Task $task): bool => $task->project
                && Gate::forUser($reader)->allows('view', $task));
        return $this->contextualSearch
            ->prioritize(
                $visible,
                $term,
                $contextIds,
                fn (Model $task): int => $contextTaskIds !== null
                    ? (int) $task->getKey()
                    : (int) $task->getAttribute('project_id'),
            )
            ->map(fn (array $result): array => $this->result($result['target'], $result['scope']))
            ->values();
    }

    /**
     * @return array{status: string, type: string, label?: string, url?: string, message?: string, accessible_name?: string}
     */
    public function present(string $key, ?User $reader): array
    {
        $task = Task::withTrashed()->with('project')->whereKey($key)->first();

        if (! $task || $task->trashed() || ! $task->project) {
            return [
                'status' => 'missing',
                'type' => 'tarefa',
                'message' => 'Menção a tarefa: destino não encontrado',
            ];
        }

        if (! $reader || ! Gate::forUser($reader)->allows('view', $task)) {
            return [
                'status' => 'forbidden',
                'type' => 'tarefa',
                'message' => 'Menção a tarefa: você não tem permissão para visualizar',
            ];
        }

        return [
            'status' => 'available',
            'type' => 'tarefa',
            'label' => $task->title,
            'url' => route('tasks.show', $task),
            'accessible_name' => 'tarefa: ' . $task->title,
        ];
    }

    private function resolve(string $key): ?Task
    {
        if (! preg_match('/^[1-9][0-9]*$/', $key)) {
            return null;
        }

        $task = Task::query()->with('project')->find($key);

        return $task?->project ? $task : null;
    }

    /** @return Collection<int, int> */
    private function contextProjectIds(Model $source): Collection
    {
        return $this->contextResolver
            ->forTaskSearch($source)
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();
    }

    /** @return Collection<int, int>|null */
    private function contextualTaskIds(Model $source): ?Collection
    {
        $tasks = $this->contextResolver->forTaskTargetSearch($source);

        return $tasks?->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();
    }

    /** @return array{id: int, name: string, type: string, type_label: string, group: string, scope: 'contextual'|'global', completed: bool} */
    private function result(Task $task, string $scope): array
    {
        return [
            'id' => (int) $task->getKey(),
            'name' => $task->title,
            'type' => self::ALIAS,
            'type_label' => 'Tarefa',
            'group' => 'tasks',
            'scope' => $scope,
            'completed' => $task->status === TaskStatus::DONE,
        ];
    }
}
