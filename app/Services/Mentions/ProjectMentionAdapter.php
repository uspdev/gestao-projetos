<?php

namespace App\Services\Mentions;

use App\Models\Comment;
use App\Models\Meeting;
use App\Models\MeetingItem;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

final class ProjectMentionAdapter
{
    public const ALIAS = 'project';

    public function supports(string $type): bool
    {
        return $type === self::ALIAS;
    }

    public function exists(string $key): bool
    {
        return preg_match('/^[1-9][0-9]*$/', $key) === 1
            && Project::query()->whereKey($key)->exists();
    }

    public function historicalExists(string $key): bool
    {
        return preg_match('/^[1-9][0-9]*$/', $key) === 1
            && Project::withTrashed()->whereKey($key)->exists();
    }

    public function isEligible(Model $source, string $key, ?User $reader = null): bool
    {
        $project = $this->resolve($key);

        if (! $project || ($source instanceof Project && $source->is($project))) {
            return false;
        }

        return $reader !== null
            && Gate::forUser($reader)->allows('view', $project);
    }

    /**
     * @return Collection<int, array{id: int, name: string, type: string, type_label: string, group: string}>
     */
    public function search(Model $source, string $term = '', ?User $reader = null): Collection
    {
        if (! $reader) {
            return collect();
        }

        $projects = Project::query()
            ->when(trim($term) !== '', fn ($query) => $query->where('name', 'like', '%' . trim($term) . '%'))
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
        $contextIds = $this->contextProjectIds($source);
        $excludedIds = $source instanceof Project ? collect([(int) $source->getKey()]) : collect();

        $visible = $projects
            ->reject(fn (Project $project): bool => $excludedIds->contains((int) $project->getKey()))
            ->filter(fn (Project $project): bool => Gate::forUser($reader)->allows('view', $project));

        $contextual = $visible
            ->filter(fn (Project $project): bool => $contextIds->contains((int) $project->getKey()))
            ->sortBy(fn (Project $project): int => (int) $contextIds->search((int) $project->getKey()))
            ->values();
        $global = $visible
            ->reject(fn (Project $project): bool => $contextIds->contains((int) $project->getKey()))
            ->values();

        return $contextual
            ->concat($global)
            ->map(fn (Project $project): array => $this->result($project))
            ->values();
    }

    /**
     * @return array{status: string, type: string, label?: string, url?: string, message?: string, accessible_name?: string}
     */
    public function present(string $key, ?User $reader): array
    {
        $project = Project::withTrashed()->whereKey($key)->first();

        if (! $project || $project->trashed()) {
            return [
                'status' => 'missing',
                'type' => 'projeto',
                'message' => 'Menção a projeto: destino não encontrado',
            ];
        }

        if (! $reader || ! Gate::forUser($reader)->allows('view', $project)) {
            return [
                'status' => 'forbidden',
                'type' => 'projeto',
                'message' => 'Menção a projeto: você não tem permissão para visualizar',
            ];
        }

        return [
            'status' => 'available',
            'type' => 'projeto',
            'label' => $project->name,
            'url' => route('projects.show', $project),
            'accessible_name' => 'projeto: ' . $project->name,
        ];
    }

    private function resolve(string $key): ?Project
    {
        if (! preg_match('/^[1-9][0-9]*$/', $key)) {
            return null;
        }

        return Project::query()->find($key);
    }

    /** @return array{id: int, name: string, type: string, type_label: string, group: string} */
    private function result(Project $project): array
    {
        return [
            'id' => (int) $project->getKey(),
            'name' => $project->name,
            'type' => self::ALIAS,
            'type_label' => 'Projeto',
            'group' => 'projects',
        ];
    }

    /** @return Collection<int, int> */
    private function contextProjectIds(Model $source): Collection
    {
        $projects = match (true) {
            $source instanceof Project => $this->relatedProjects($source),
            $source instanceof Task => $this->contextualProjects($source->loadMissing('project')->project),
            $source instanceof Meeting => $source->loadMissing('projects')->projects,
            $source instanceof MeetingItem => $source->loadMissing('meeting.projects')->meeting?->projects ?? collect(),
            $source instanceof Comment => $this->commentContextProjects($source),
            default => collect(),
        };

        return collect($projects)
            ->filter(fn (mixed $project): bool => $project instanceof Project)
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();
    }

    /** @return Collection<int, Project> */
    private function relatedProjects(Project $project): Collection
    {
        if (! Schema::hasColumn($project->getTable(), 'parent_id')) {
            return collect();
        }

        $project->loadMissing('parent');

        return collect([$project->parent])
            ->filter()
            ->merge($project->children()->get())
            ->values();
    }

    /** @return Collection<int, Project> */
    private function commentContextProjects(Comment $comment): Collection
    {
        $comment->loadMissing('commentable');

        return match (true) {
            $comment->commentable instanceof Project => collect([$comment->commentable]),
            $comment->commentable instanceof Task => $this->contextualProjects(
                $comment->commentable->loadMissing('project')->project
            ),
            $comment->commentable instanceof Meeting => $comment->commentable->loadMissing('projects')->projects,
            default => collect(),
        };
    }

    /** @return Collection<int, Project> */
    private function contextualProjects(?Project $project): Collection
    {
        return $project
            ? collect([$project])->concat($this->relatedProjects($project))->values()
            : collect();
    }
}
