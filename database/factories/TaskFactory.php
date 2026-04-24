<?php

namespace Database\Factories;

use App\Enums\Task\TaskPriority;
use App\Enums\Task\TaskStatus;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Cache;
use App\Models\Tag;

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
        $priorities = array_column(TaskPriority::cases(), 'value');

        $startDate = $this->faker->dateTimeBetween('-2 months', '+2 weeks');

        return [
            'project_id' => Project::factory(),
            'title' => $this->faker->sentence(6),
            'description' => $this->faker->optional()->paragraph(),
            'priority' => $this->faker->optional()->randomElement($priorities),
            'status' => $this->faker->randomElement($statuses),
            'start_date' => $startDate,
            'due_date' => $this->faker->optional()->dateTimeBetween($startDate, '+3 months'),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Task $task) {
            // Cache de 1 minuto para não estourar o banco se criar 1000 tasks
            $availableTagIds = Cache::remember('factory_task_tags', 60, function () {
                return Tag::getWithType('tasks')->pluck('id')->toArray();
            });

            if (!empty($availableTagIds)) {
                $selectedIds = $this->faker->randomElements($availableTagIds, $this->faker->numberBetween(0, 2));
                
                $task->tags()->attach($selectedIds);
            }
        });
    }
}
