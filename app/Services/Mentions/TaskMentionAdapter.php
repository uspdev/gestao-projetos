<?php

namespace App\Services\Mentions;

use App\Models\Comment;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

final class TaskMentionAdapter
{
    public const ALIAS = 'task';

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
     * @return Collection<int, array{id: int, name: string, type: string, type_label: string, group: string}>
     */
    public function search(Model $source, string $term = '', ?User $reader = null): Collection
    {
        if (! $reader || ! Schema::hasTable('tasks')) {
            return collect();
        }

        $tasks = Task::query()
            ->with('project')
            ->when(trim($term) !== '', fn ($query) => $query->where(
                'title',
                'like',
                '%' . trim($term) . '%'
            ))
            ->orderBy('title')
            ->get(['id', 'project_id', 'title']);
        $contextProjectIds = $this->contextProjectIds($source);
        $excludedIds = $source instanceof Task
            ? collect([(int) $source->getKey()])
            : collect();

        $visible = $tasks
            ->reject(fn (Task $task): bool => $excludedIds->contains((int) $task->getKey()))
            ->filter(fn (Task $task): bool => $task->project
                && Gate::forUser($reader)->allows('view', $task));
        $contextual = $visible
            ->filter(fn (Task $task): bool => $contextProjectIds->contains((int) $task->project_id))
            ->sortBy(function (Task $task) use ($contextProjectIds): int {
                return (int) $contextProjectIds->search((int) $task->project_id);
            })
            ->values();
        $global = $visible
            ->reject(fn (Task $task): bool => $contextProjectIds->contains((int) $task->project_id))
            ->values();

        return $contextual
            ->concat($global)
            ->map(fn (Task $task): array => $this->result($task))
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
        $project = match (true) {
            $source instanceof Project => $source,
            $source instanceof Task => $source->loadMissing('project')->project,
            $source instanceof Comment => $this->commentProject($source),
            default => null,
        };

        return $project ? collect([(int) $project->getKey()]) : collect();
    }

    private function commentProject(Comment $comment): ?Project
    {
        $comment->loadMissing('commentable');

        return match (true) {
            $comment->commentable instanceof Project => $comment->commentable,
            $comment->commentable instanceof Task => $comment->commentable
                ->loadMissing('project')->project,
            default => null,
        };
    }

    /** @return array{id: int, name: string, type: string, type_label: string, group: string} */
    private function result(Task $task): array
    {
        return [
            'id' => (int) $task->getKey(),
            'name' => $task->title,
            'type' => self::ALIAS,
            'type_label' => 'Tarefa',
            'group' => 'tasks',
        ];
    }
}
