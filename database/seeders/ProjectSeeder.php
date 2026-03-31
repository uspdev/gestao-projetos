<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::query()->get();

        if ($users->isEmpty()) {
            return;
        }

        $projects = Project::factory()->count(8)->create();

        foreach ($projects as $project) {
            $members = $users->random(random_int(2, min(4, $users->count())));
            $project->users()->syncWithoutDetaching($members->pluck('id')->all());
        }
    }
}
