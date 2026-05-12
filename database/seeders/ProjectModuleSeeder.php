<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProjectModuleSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('projects') || !Schema::hasTable('modules') || !Schema::hasTable('project_modules')) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $developmentTypeId = null;
        if (Schema::hasTable('project_types')) {
            $developmentTypeId = DB::table('project_types')->where('slug', 'desenvolvimento')->value('id');
            if ($developmentTypeId) {
                DB::table('projects')->whereNull('project_type_id')->update(['project_type_id' => $developmentTypeId]);
            }
        }

        $modules = DB::table('modules')->pluck('id', 'slug');
        $projects = DB::table('projects')->select('id', 'project_type_id')->get();

        foreach ($projects as $project) {
            $defaultsUsed = false;

            $projectTypeId = $project->project_type_id ?: $developmentTypeId;

            if (Schema::hasTable('project_type_modules') && $projectTypeId) {
                $rows = DB::table('project_type_modules')
                    ->where('project_type_id', $projectTypeId)
                    ->get();

                if ($rows->isNotEmpty()) {
                    foreach ($rows as $row) {
                        DB::table('project_modules')->updateOrInsert(
                            ['project_id' => $project->id, 'module_id' => $row->module_id],
                            ['enabled' => (bool) $row->enabled, 'created_at' => $now, 'updated_at' => $now]
                        );
                    }

                    $defaultsUsed = true;
                }
            }

            if (!$defaultsUsed) {
                foreach ($modules as $slug => $moduleId) {
                    $enabled = ($slug === 'tasks');

                    DB::table('project_modules')->updateOrInsert(
                        ['project_id' => $project->id, 'module_id' => $moduleId],
                        ['enabled' => $enabled, 'created_at' => $now, 'updated_at' => $now]
                    );
                }
            }
        }
    }
}
