<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        Module::query()->updateOrCreate(
            ['slug' => 'tasks'],
            [
                'name' => 'Tarefas',
                'description' => 'Gerenciamento de tarefas por projeto.',
            ]
        );
    }
}
