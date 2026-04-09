<?php

namespace Database\Factories;

use App\Enums\Task\TaskLabel;
use App\Enums\Task\TaskPriority;
use App\Enums\Task\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $statuses = array_column(TaskStatus::cases(), 'value');
        $labels = array_column(TaskLabel::cases(), 'value');
        $priorities = array_column(TaskPriority::cases(), 'value');

        $startDate = $this->faker->dateTimeBetween('-2 months', '+2 weeks');

        return [
            'project_id' => Project::factory(),
            'title' => $this->faker->sentence(6),
            'description' => $this->faker->optional()->paragraph(),
            'priority' => $this->faker->optional()->randomElement($priorities),
            'status' => $this->faker->randomElement($statuses),
            'label' => $this->faker->optional()->randomElement($labels),
            'start_date' => $startDate,
            'due_date' => $this->faker->optional()->dateTimeBetween($startDate, '+3 months'),
        ];
    }
}
