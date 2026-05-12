<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\ProjectTypeModule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ProjectTypeModuleSeeder extends Seeder
{
    public function run(): void
    {
        if (!Schema::hasTable('project_types') || !Schema::hasTable('project_type_modules')) {
            return;
        }

        $taskModule = Module::query()->where('slug', 'tasks')->first();
        if (!$taskModule) {
            return;
        }

        $projectTypeIds = DB::table('project_types')->pluck('id');

        foreach ($projectTypeIds as $projectTypeId) {
            ProjectTypeModule::query()->updateOrCreate(
                [
                    'project_type_id' => $projectTypeId,
                    'module_id' => $taskModule->id,
                ],
                [
                    'enabled' => true,
                    'required' => false,
                    'editable' => true,
                    'config' => null,
                ]
            );
        }
    }
}
