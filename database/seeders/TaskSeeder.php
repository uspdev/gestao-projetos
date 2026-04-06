<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        $adminUsers = $this->resolveAdminUsers();
        $projects = Project::query()->with('users:id')->get();
        $allUsers = User::query()->pluck('id')->merge($adminUsers->pluck('id'))->unique()->values();

        if ($projects->isEmpty() || $allUsers->isEmpty()) {
            return;
        }

        foreach ($projects as $project) {
            $projectUserIds = $project->users->pluck('id')
                ->merge($adminUsers->pluck('id'))
                ->unique()
                ->values();

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
                    ? $project->users->pluck('id')->merge($adminUsers->pluck('id'))->unique()->values()
                    : $allUsers;

                $assignees = $candidateUsers->shuffle()->take(random_int(1, min(3, $candidateUsers->count())));
                $assigneeIds = $assignees
                    ->merge($adminUsers->pluck('id'))
                    ->unique()
                    ->values()
                    ->all();

                $task->users()->syncWithoutDetaching($assigneeIds);
            }
        }
    }

    private function resolveAdminUsers(): Collection
    {
        $adminCodpes = collect(explode(',', (string) env('SENHAUNICA_ADMINS', '')))
            ->map(fn (string $codpes): string => trim($codpes))
            ->filter(fn (string $codpes): bool => $codpes !== '');

        if ($adminCodpes->isEmpty()) {
            return collect();
        }

        return $adminCodpes->map(function (string $codpes): User {
            return User::query()->firstOrCreate(
                ['codpes' => (int) $codpes],
                [
                    'name' => 'Admin ' . $codpes,
                    'email' => 'admin' . $codpes . '@seed.local',
                    'password' => null,
                ]
            );
        });
    }
}
