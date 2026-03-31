<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $projects = Project::query()->with('users:id')->get();
        $allUsers = User::query()->pluck('id');

        if ($projects->isEmpty() || $allUsers->isEmpty()) {
            return;
        }

        foreach ($projects as $project) {
            $projectUserIds = $project->users->pluck('id');

            $randomCreatorId = $projectUserIds->isNotEmpty() 
                           ? $projectUserIds->random() 
                           : $allUsers->random();

            $tasks = Task::factory()
                ->count(random_int(4, 10))
                ->for($project)
                ->create([
                    'created_by' => $randomCreatorId,
                ]);

            foreach ($tasks as $task) {
                $candidateUsers = $project->users->isNotEmpty()
                    ? $project->users->pluck('id')
                    : $allUsers;

                $assignees = $candidateUsers->shuffle()->take(random_int(1, min(3, $candidateUsers->count())));
                $task->users()->syncWithoutDetaching($assignees->all());
            }
        }
    }
}
