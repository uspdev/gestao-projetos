<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProjectTypeModuleSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('project_types') || !Schema::hasTable('modules') || !Schema::hasTable('project_type_modules')) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $projectTypeId = DB::table('project_types')->where('slug', 'desenvolvimento')->value('id');
        if (!$projectTypeId) {
            return;
        }

        $moduleIds = DB::table('modules')->whereIn('slug', ['tasks', 'meetings'])->pluck('id', 'slug');

        foreach ($moduleIds as $slug => $moduleId) {
            $enabled = $slug === 'tasks';

            DB::table('project_type_modules')->updateOrInsert(
                ['project_type_id' => $projectTypeId, 'module_id' => $moduleId],
                [
                    'enabled' => $enabled,
                    'required' => false,
                    'editable' => true,
                    'config' => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }
    }
}
