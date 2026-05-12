<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('modules')) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $modules = [
            [
                'slug' => 'tasks',
                'name' => 'Tarefas',
                'description' => 'Gerenciamento de tarefas por projeto.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'meetings',
                'name' => 'Reunioes',
                'description' => 'Agenda e atas de reunioes do projeto.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($modules as $module) {
            DB::table('modules')->updateOrInsert(
                ['slug' => $module['slug']],
                $module
            );
        }
    }
}
