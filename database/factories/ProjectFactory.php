<?php

namespace Database\Factories;

use App\Enums\Project\ProjectPermissionInheritance;
use App\Enums\Project\ProjectPhase;
use App\Enums\Project\ProjectStatus;
use App\Enums\Project\ProjectVisibility;
use App\Models\Project;
use App\Models\ProjectType;
use App\Models\Tag;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Project>
 */
class ProjectFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = array_column(ProjectStatus::cases(), 'value');
        $visibilities = array_column(ProjectVisibility::cases(), 'value');
        $permissionInheritance = array_column(ProjectPermissionInheritance::cases(), 'value');
        $phases = array_column(ProjectPhase::cases(), 'value');

        $projectTypeId = null;
        if (Schema::hasTable('project_types')) {
            $projectTypeId = ProjectType::query()->inRandomOrder()->value('id');
        }

        return [
            'name' => $this->faker->unique()->sentence(3),
            'status' => $this->faker->randomElement($statuses),
            'description' => $this->faker->optional()->paragraph(),
            'project_type_id' => $this->faker->boolean(70) ? $projectTypeId : null,
            'visibility' => $this->faker->randomElement($visibilities),
            'permission_inheritance' => $this->faker->randomElement($permissionInheritance),
            'phase' => $this->faker->randomElement($phases),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Project $project) {
            // Cache de 1 minuto para não estourar o banco se criar 1000 projetos
            $availableTagIds = Cache::remember('factory_project_tags', 60, function () {
                return Tag::forProjects()->pluck('id')->toArray();
            });

            if (!empty($availableTagIds)) {
                $selectedIds = $this->faker->randomElements($availableTagIds, $this->faker->numberBetween(0, 2));

                $project->tags()->attach($selectedIds);
            }
        });
    }
}
