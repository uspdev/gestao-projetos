<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Este seeder é responsável por popular a tabela de project_modules com as configurações padrão de módulos para cada projeto,
    // considerando o tipo de projeto associado a cada projeto. Ele garante que cada projeto tenha uma configuração
    // inicial de módulos, mesmo que o tipo de projeto ou os módulos sejam criados após os projetos
    public function up(): void
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
            // Verificar se o projeto já tem configurações de módulos para evitar sobrescrever configurações existentes
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
                // Fallback: habilitar apenas o modulo de tarefas.
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

    public function down(): void
    {
        if (!Schema::hasTable('project_modules') || !Schema::hasTable('modules')) {
            return;
        }

        $slugs = ['tasks', 'meetings', 'phases'];
        $moduleIds = DB::table('modules')->whereIn('slug', $slugs)->pluck('id')->all();

        if (!empty($moduleIds)) {
            DB::table('project_modules')->whereIn('module_id', $moduleIds)->delete();
        }
    }
};
