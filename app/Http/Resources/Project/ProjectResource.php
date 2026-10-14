<?php

namespace App\Http\Resources\Project;

use App\Http\Resources\Shared\ApiDetailResource;
use App\Http\Resources\Shared\TagResource;
use App\Models\Phase;
use App\Models\Project;
use App\Models\ProjectType;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Request;

class ProjectResource extends ApiDetailResource
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
            'visibility' => [
                'value' => $project->visibility->value,
                'label' => $project->visibility->label(),
            ],
            'permission_inheritance' => [
                'value' => $project->permission_inheritance->value,
                'label' => $project->permission_inheritance->label(),
            ],
            'type' => $this->projectTypeSummary($project->projectType),
            'phase' => $this->phaseSummary($project->phase),
            'parent' => $project->parent ? (new ProjectSummaryResource($project->parent))->resolve($request) : null,
            'tags' => $project->tags
                ->map(fn (Tag $tag): array => (new TagResource($tag))->resolve($request))
                ->values()
                ->all(),
            'modules_enabled' => $modules
                ->filter(fn (array $module): bool => $module['enabled'])
                ->map(fn (array $module): array => [
                    'slug' => $module['slug'],
                    'name' => $module['name'],
                    'enabled' => $module['enabled'],
                ])
                ->values()
                ->all(),
            'members' => $project->users
                ->map(fn (User $user): array => (new ProjectMemberResource($user, $project))->resolve($request))
                ->values()
                ->all(),
            ...$this->visibleDetail($request),
            'agenda_meetings' => collect($project->getRelation('apiAgendaMeetings'))
                ->map(fn ($meeting): array => [
                    'id' => $meeting->id,
                    'title' => $meeting->title,
                    'status' => [
                        'value' => $meeting->status->value,
                        'label' => $meeting->status->label(),
                    ],
                    'scheduled_at' => $meeting->scheduled_at?->toISOString(),
                    'web_url' => route('projects.meetings.show', [$project, $meeting]),
                ])
                ->values()
                ->all(),
            'subprojects' => collect($project->getRelation('apiSubprojects'))
                ->map(fn (Project $subproject): array => [
                    ...(new ProjectSummaryResource($subproject))->resolve($request),
                    'status' => [
                        'value' => $subproject->status->value,
                        'label' => $subproject->status->label(),
                    ],
                    'type' => $this->projectTypeSummary($subproject->projectType),
                    'tags' => $subproject->tags
                        ->map(fn (Tag $tag): array => (new TagResource($tag))->resolve($request))
                        ->values()
                        ->all(),
                ])
                ->values()
                ->all(),
            'web_url' => route('projects.show', $project),
            'created_at' => $project->created_at?->toISOString(),
            'updated_at' => $project->updated_at?->toISOString(),
        ];
    }

    /**
     * @return array{id: int, slug: string, name: string, description: string|null}|null
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
            'description' => $projectType->description,
        ];
    }

    /**
     * @return array{id: int, slug: string, name: string, color: string|null}|null
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
            'color' => $phase->color,
        ];
    }
}
