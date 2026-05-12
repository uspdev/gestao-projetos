<?php

namespace Database\Seeders;

use Database\Seeders\ProjectTypeSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment(['local', 'testing'])) {
            $this->call([
                ModuleSeeder::class,
            ]);
        }

        $this->call([
            UserSeeder::class,
            ProjectSeeder::class,
        ]);

        if (app()->environment(['local', 'testing'])) {
            $this->call([
                ProjectTypeSeeder::class,
                ProjectTypeModuleSeeder::class,
            ]);
        }

        $this->call([
            ProjectModuleSeeder::class,
            TaskSeeder::class,
        ]);
    }
}
