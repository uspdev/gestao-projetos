<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->count(10)->create();

        $this->ensureSenhaunicaHierarchyPermissions();
        $this->assignAdminPermissionFromEnv();
    }

    private function ensureSenhaunicaHierarchyPermissions(): void
    {
        foreach (User::$permissoesHierarquia as $permissionName) {
            Permission::findOrCreate($permissionName, User::$hierarquiaNs);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function assignAdminPermissionFromEnv(): void
    {
        $adminPermission = Permission::findOrCreate('admin', User::$hierarquiaNs);

        $adminCodpes = collect((array) config('senhaunica.admins', []))
            ->map(fn ($codpes) => trim((string) $codpes))
            ->filter();

        if ($adminCodpes->isEmpty()) {
            return;
        }

        $adminUsers = $adminCodpes->map(function (string $codpes): User {
            return User::query()->firstOrCreate(
                ['codpes' => (int) $codpes],
                [
                    'name' => 'Admin ' . $codpes,
                    'email' => 'admin' . $codpes . '@seed.local',
                    'password' => null,
                ]
            );
        });

        foreach ($adminUsers as $user) {
            $user->givePermissionTo($adminPermission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}