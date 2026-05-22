<?php

use App\Enums\Project\ProjectPhase;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Essa migration cria o módulo de fases,
    // as fases padrão do ciclo de vida do projeto e associar essas fases aos tipos de projeto de software.
    // Ela pode ser executada várias vezes sem causar duplicações ou erros,
    // graças ao uso de updateOrInsert e verificações de existência.
    public function up(): void
    {
        $now = date('Y-m-d H:i:s');

        if (Schema::hasTable('modules')) {
            $phaseModule = [
                'slug' => 'phases',
                'name' => 'Fases',
                'description' => 'Fases do ciclo de vida do projeto.',
                'created_at' => $now,
                'updated_at' => $now,
            ];

            DB::table('modules')->updateOrInsert(
                ['slug' => $phaseModule['slug']],
                $phaseModule
            );
        }

        if (Schema::hasTable('project_types') && Schema::hasTable('project_type_modules') && Schema::hasTable('modules')) {
            $softwareTypeIds = DB::table('project_types')
                ->whereIn('slug', ['software', 'desenvolvimento'])
                ->pluck('id')
                ->all();

            $phaseModuleId = DB::table('modules')->where('slug', 'phases')->value('id');

            if ($phaseModuleId) {
                foreach ($softwareTypeIds as $typeId) {
                    DB::table('project_type_modules')->updateOrInsert(
                        ['project_type_id' => $typeId, 'module_id' => $phaseModuleId],
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

        if (Schema::hasTable('phases')) {
            foreach (ProjectPhase::cases() as $phase) {
                $slug = strtolower($phase->value);
                $isInitial = $phase === ProjectPhase::PLANNING;
                $isFinal = $phase === ProjectPhase::RETIRED;

                DB::table('phases')->updateOrInsert(
                    ['slug' => $slug],
                    [
                        'name' => $phase->label(),
                        'slug' => $slug,
                        'color' => $phase->color(),
                        'description' => null,
                        'is_active' => true,
                        'is_initial' => $isInitial,
                        'is_final' => $isFinal,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }

        if (Schema::hasTable('project_type_phases') && Schema::hasTable('project_types') && Schema::hasTable('phases')) {
            $softwareTypeIds = DB::table('project_types')
                ->whereIn('slug', ['software', 'desenvolvimento'])
                ->pluck('id')
                ->all();

            $orderedSlugs = [
                strtolower(ProjectPhase::PLANNING->value),
                strtolower(ProjectPhase::DEVELOPMENT->value),
                strtolower(ProjectPhase::PRODUCTION->value),
                strtolower(ProjectPhase::RETIRED->value),
            ];

            $phaseIds = DB::table('phases')
                ->whereIn('slug', $orderedSlugs)
                ->pluck('id', 'slug');

            foreach ($softwareTypeIds as $typeId) {
                foreach ($orderedSlugs as $index => $slug) {
                    $phaseId = $phaseIds[$slug] ?? null;
                    if (! $phaseId) {
                        continue;
                    }

                    DB::table('project_type_phases')->updateOrInsert(
                        ['project_type_id' => $typeId, 'phase_id' => $phaseId],
                        [
                            'order' => $index + 1,
                            'is_initial' => $slug === strtolower(ProjectPhase::PLANNING->value),
                            'is_final' => $slug === strtolower(ProjectPhase::RETIRED->value),
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]
                    );
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('project_type_phases') && Schema::hasTable('phases')) {
            $slugs = [
                strtolower(ProjectPhase::PLANNING->value),
                strtolower(ProjectPhase::DEVELOPMENT->value),
                strtolower(ProjectPhase::PRODUCTION->value),
                strtolower(ProjectPhase::RETIRED->value),
            ];

            $phaseIds = DB::table('phases')->whereIn('slug', $slugs)->pluck('id')->all();

            if (! empty($phaseIds)) {
                DB::table('project_type_phases')->whereIn('phase_id', $phaseIds)->delete();
            }
        }

        if (Schema::hasTable('phases')) {
            $slugs = [
                strtolower(ProjectPhase::PLANNING->value),
                strtolower(ProjectPhase::DEVELOPMENT->value),
                strtolower(ProjectPhase::PRODUCTION->value),
                strtolower(ProjectPhase::RETIRED->value),
            ];

            DB::table('phases')->whereIn('slug', $slugs)->delete();
        }

        if (Schema::hasTable('project_type_modules') && Schema::hasTable('modules')) {
            $phaseModuleId = DB::table('modules')->where('slug', 'phases')->value('id');

            if ($phaseModuleId) {
                DB::table('project_type_modules')->where('module_id', $phaseModuleId)->delete();
            }
        }

        if (Schema::hasTable('modules')) {
            DB::table('modules')->where('slug', 'phases')->delete();
        }
    }
};
