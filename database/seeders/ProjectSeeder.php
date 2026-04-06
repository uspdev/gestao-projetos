<?php

namespace Database\Seeders;

use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::query()->get();
        $adminUsers = $this->resolveAdminUsers();
        $users = $users->merge($adminUsers)->unique('id')->values();

        if ($users->isEmpty()) {
            return;
        }

        $projects = Project::factory()->count(8)->create();

        foreach ($projects as $project) {
            $members = $users->random(random_int(2, min(4, $users->count())));
            $memberIds = $members
                ->pluck('id')
                ->merge($adminUsers->pluck('id'))
                ->unique()
                ->values()
                ->all();

            $project->users()->syncWithoutDetaching($memberIds);
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
