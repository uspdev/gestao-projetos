<?php

namespace Database\Factories;

use App\Enums\ProjectStatus;
use App\Models\Project;
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
}
