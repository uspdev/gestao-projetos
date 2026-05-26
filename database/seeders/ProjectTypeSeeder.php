<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProjectTypeSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('project_types')) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $developmentType = [
            'name' => 'Desenvolvimento',
            'slug' => 'desenvolvimento',
            'description' => 'Tipo de projeto para desenvolvimento.',
            'enabled' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        DB::table('project_types')->updateOrInsert(
            ['slug' => $developmentType['slug']],
            $developmentType
        );

        if (!Schema::hasTable('projects')) {
            return;
        }

        $developmentTypeId = DB::table('project_types')
            ->where('slug', $developmentType['slug'])
            ->value('id');

        if ($developmentTypeId) {
            DB::table('projects')->whereNull('project_type_id')->update(['project_type_id' => $developmentTypeId]);
        }
    }
}
