<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Esta migration é responsável por popular as tabelas de módulos,
    // tipos de projeto e suas relações, garantindo que tenhamos uma configuração inicial
    // para os tipos de projeto "Software", "Organizacional", "Acadêmico" e "Infraestrutura".
    // Ela também atualiza os projetos existentes para associá-los ao tipo de projeto "Desenvolvimento"
    // caso ainda não tenham um tipo definido. Isso foi feito para garantir que os projetos existentes tenham
    // uma configuração de módulos, não quebrando com essa implementação
    public function up(): void
    {
        if (!Schema::hasTable('modules') || !Schema::hasTable('project_types') || !Schema::hasTable('project_type_modules')) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $developmentType = [
            'name' => 'Software',
            'slug' => 'software',
            'description' => 'Projeto operacional com fluxo de desenvolvimento, acompanhamento, gerenciamento de entregas, produção e atualizações.',
            'enabled' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        DB::table('project_types')->updateOrInsert(
            ['slug' => $developmentType['slug']],
            $developmentType
        );

        $developmentTypeId = DB::table('project_types')
            ->where('slug', $developmentType['slug'])
            ->value('id');

        $organizationalType = [
            'name' => 'Organizacional',
            'slug' => 'organizacional',
            'description' =>  'Estrutura organizacional para agrupar múltiplos projetos relacionados, permitindo gestão centralizada e visão
            consolidada.<br>
            Dentro do <b>container</b> pode-se criar quaisquer outros tipos de projetos.',
            'enabled' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        DB::table('project_types')->updateOrInsert(
            ['slug' => $organizationalType['slug']],
            $organizationalType
        );

        $organizationalTypeId = DB::table('project_types')
            ->where('slug', $organizationalType['slug'])
            ->value('id');

        $academicType = [
            'name' => 'Acadêmico',
            'slug' => 'academico',
            'description' => 'Projeto acadêmico para acompanhamento de pesquisa e produção científica, com reuniões e tarefas opcionais.',
            'enabled' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        DB::table('project_types')->updateOrInsert(
            ['slug' => $academicType['slug']],
            $academicType
        );

        $academicTypeId = DB::table('project_types')
            ->where('slug', $academicType['slug'])
            ->value('id');

        $infrastructureType = [
            'name' => 'Infraestrutura',
            'slug' => 'infraestrutura',
            'description' => 'Projeto de infraestrutura com reuniões e tarefas opcionais, voltado a iniciativas operacionais.',
            'enabled' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        DB::table('project_types')->updateOrInsert(
            ['slug' => $infrastructureType['slug']],
            $infrastructureType
        );

        $infrastructureTypeId = DB::table('project_types')
            ->where('slug', $infrastructureType['slug'])
            ->value('id');

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
                'name' => 'Reuniões',
                'description' => 'Agenda e atas de reuniões do projeto.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'slug' => 'phases',
                'name' => 'Fases',
                'description' => 'Fases do ciclo de vida do projeto.',
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        foreach ($modules as $m) {
            DB::table('modules')->updateOrInsert(
                ['slug' => $m['slug']],
                $m
            );
        }

        if (Schema::hasTable('projects') && $developmentTypeId) {
            DB::table('projects')->whereNull('project_type_id')->update(['project_type_id' => $developmentTypeId]);
        }

        $allModuleIds = DB::table('modules')->whereIn('slug', ['tasks', 'meetings', 'phases'])->pluck('id', 'slug');
        $basicModuleIds = $allModuleIds->only(['tasks', 'meetings']);
        $meetingModuleIds = $allModuleIds->only(['meetings']);

        $projectTypeModuleMap = [
            $developmentTypeId => $allModuleIds,
            $organizationalTypeId => $meetingModuleIds,
            $academicTypeId => $basicModuleIds,
            $infrastructureTypeId => $basicModuleIds,
        ];

        foreach ($projectTypeModuleMap as $projectTypeId => $typeModuleIds) {
            if (!$projectTypeId) {
                continue;
            }

            foreach ($typeModuleIds as $moduleId) {
                DB::table('project_type_modules')->updateOrInsert(
                    ['project_type_id' => $projectTypeId, 'module_id' => $moduleId],
                    [
                        'enabled' => true,
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

    public function down(): void
    {
        if (Schema::hasTable('project_type_modules') && Schema::hasTable('modules')) {
            $moduleIds = DB::table('modules')->whereIn('slug', ['tasks', 'meetings', 'phases'])->pluck('id')->all();
            if (!empty($moduleIds)) {
                DB::table('project_type_modules')->whereIn('module_id', $moduleIds)->delete();
            }
        }

        if (Schema::hasTable('modules')) {
            DB::table('modules')->whereIn('slug', ['tasks', 'meetings', 'phases'])->delete();
        }
    }
};
