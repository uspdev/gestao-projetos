<?php

namespace App\Http\Resources;

use App\Models\Phase;
use App\Models\Project;
use App\Models\ProjectType;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProjectResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Project $project */
        $project = $this->resource;
        $modules = collect($project->resolvedModules());

        return [
            'id' => $project->id,
            'slug' => $project->slug,
            'name' => $project->name,
            'description' => $project->description,
            'status' => [
                'value' => $project->status->value,
                'label' => $project->status->label(),
            ],
            'type' => $this->projectTypeSummary($project->projectType),
            'phase' => $this->phaseSummary($project->phase),
            'parent' => $this->projectSummary($project->parent),
            'tags' => $project->tags
                ->map(fn (Tag $tag): array => [
                    'id' => $tag->id,
                    'name' => $tag->name,
                    'slug' => $tag->slug,
                ])
                ->values()
                ->all(),
            'modules' => [
                'enabled' => $modules
                    ->filter(fn (array $module): bool => $module['enabled'])
                    ->pluck('slug')
                    ->values()
                    ->all(),
                'tasks_enabled' => (bool) data_get(
                    $modules->firstWhere('slug', 'tasks'),
                    'enabled',
                    false,
                ),
            ],
            'web_url' => route('projects.show', $project),
            'created_at' => $project->created_at?->toISOString(),
            'updated_at' => $project->updated_at?->toISOString(),
        ];
    }

    /**
     * @return array{id: int, slug: string, name: string}|null
     */
    private function projectTypeSummary(?ProjectType $projectType): ?array
    {
        if ($projectType === null) {
            return null;
        }

        return [
            'id' => $projectType->id,
            'slug' => $projectType->slug,
            'name' => $projectType->name,
        ];
    }

    /**
     * @return array{id: int, slug: string, name: string}|null
     */
    private function phaseSummary(?Phase $phase): ?array
    {
        if ($phase === null) {
            return null;
        }

        return [
            'id' => $phase->id,
            'slug' => $phase->slug,
            'name' => $phase->name,
        ];
    }

    /**
     * @return array{id: int, slug: string, name: string}|null
     */
    private function projectSummary(?Project $project): ?array
    {
        if ($project === null) {
            return null;
        }

        return [
            'id' => $project->id,
            'slug' => $project->slug,
            'name' => $project->name,
        ];
    }
}
