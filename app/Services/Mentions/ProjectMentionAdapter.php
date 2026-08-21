<?php

namespace App\Services\Mentions;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;

final class ProjectMentionAdapter
{
    public const ALIAS = 'project';

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
     * @return Collection<int, array{id: int, name: string, type: string, type_label: string, group: string, scope: 'contextual'|'global'}>
     */
    public function search(Model $source, string $term = '', ?User $reader = null): Collection
    {
        if (! $reader) {
            return collect();
        }

        $term = trim($term);
        $contextIds = $this->contextProjectIds($source);

        if ($term === '' && $contextIds->isEmpty()) {
            return collect();
        }

        $projects = Project::query()
            ->when($term !== '', fn ($query) => $query->where('name', 'like', '%' . $term . '%'))
            ->when($term === '', fn ($query) => $query->whereIn('id', $contextIds->all()))
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
        $excludedIds = $source instanceof Project ? collect([(int) $source->getKey()]) : collect();

        $visible = $projects
            ->reject(fn (Project $project): bool => $excludedIds->contains((int) $project->getKey()))
            ->filter(fn (Project $project): bool => Gate::forUser($reader)->allows('view', $project));

        return $this->contextualSearch
            ->prioritize(
                $visible,
                $term,
                $contextIds,
                fn (Model $project): int => (int) $project->getKey(),
            )
            ->map(fn (array $result): array => $this->result($result['target'], $result['scope']))
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
            'url' => deep_link('projects.show', $project),
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

    /** @return array{id: int, name: string, type: string, type_label: string, group: string, scope: 'contextual'|'global'} */
    private function result(Project $project, string $scope): array
    {
        return [
            'id' => (int) $project->getKey(),
            'name' => $project->name,
            'type' => self::ALIAS,
            'type_label' => 'Projeto',
            'group' => 'projects',
            'scope' => $scope,
        ];
    }

    /** @return Collection<int, int> */
    private function contextProjectIds(Model $source): Collection
    {
        return $this->contextResolver
            ->forProjectSearch($source)
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();
    }
}
