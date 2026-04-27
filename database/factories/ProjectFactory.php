<?php

namespace Database\Factories;

use App\Enums\Project\ProjectStatus;
use App\Models\Project;
use App\Models\Tag;
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

        return [
            'name' => $this->faker->unique()->sentence(3),
            'status' => $this->faker->randomElement($statuses),
            'description' => $this->faker->optional()->paragraph(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Project $project) {
            // Cache de 1 minuto para não estourar o banco se criar 1000 projetos
            $availableTagIds = Cache::remember('factory_project_tags', 60, function () {
                return Tag::withType('projects')->pluck('id')->toArray();
            });

            if (!empty($availableTagIds)) {
                $selectedIds = $this->faker->randomElements($availableTagIds, $this->faker->numberBetween(0, 2));

                $project->tags()->attach($selectedIds);
            }
        });
    }
}
