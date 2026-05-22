<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// Essa migration adiciona a coluna phase_id à tabela projects, estabelecendo uma relação com a tabela phases.
// Ela também migra os dados da coluna de fase legada para a nova estrutura,
// garantindo que os projetos existentes mantenham suas informações de fase corretamente
// associadas às novas fases definidas na tabela phases.

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('projects')) {
            return;
        }

        if (!Schema::hasColumn('projects', 'phase_id')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->foreignId('phase_id')->nullable()->constrained('phases')->nullOnDelete();
            });
        }

        if (Schema::hasColumn('projects', 'phase') && Schema::hasTable('phases') && Schema::hasTable('project_types')) {
            $phaseMap = [
                'PLANNING' => 'planning',
                'DEVELOPMENT' => 'development',
                'PRODUCTION' => 'production',
                'RETIRED' => 'retired',
            ];

            $phaseIds = DB::table('phases')
                ->whereIn('slug', array_values($phaseMap))
                ->pluck('id', 'slug');

            $softwareTypeIds = DB::table('project_types')
                ->whereIn('slug', ['software', 'desenvolvimento'])
                ->pluck('id')
                ->all();

            if (!empty($softwareTypeIds)) {
                foreach ($phaseMap as $legacyValue => $slug) {
                    $phaseId = $phaseIds[$slug] ?? null;
                    if (! $phaseId) {
                        continue;
                    }

                    DB::table('projects')
                        ->whereIn('project_type_id', $softwareTypeIds)
                        ->where('phase', $legacyValue)
                        ->update(['phase_id' => $phaseId]);
                }
            }
        }

        if (Schema::hasColumn('projects', 'phase')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropColumn('phase');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('projects')) {
            return;
        }

        if (!Schema::hasColumn('projects', 'phase')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->string('phase')->default('PLANNING');
            });
        }

        if (Schema::hasColumn('projects', 'phase_id') && Schema::hasTable('phases')) {
            $phaseMap = [
                'planning' => 'PLANNING',
                'development' => 'DEVELOPMENT',
                'production' => 'PRODUCTION',
                'retired' => 'RETIRED',
            ];

            $phaseIds = DB::table('phases')
                ->whereIn('slug', array_keys($phaseMap))
                ->pluck('id', 'slug');

            foreach ($phaseMap as $slug => $legacyValue) {
                $phaseId = $phaseIds[$slug] ?? null;
                if (! $phaseId) {
                    continue;
                }

                DB::table('projects')
                    ->where('phase_id', $phaseId)
                    ->update(['phase' => $legacyValue]);
            }
        }

        if (Schema::hasColumn('projects', 'phase_id')) {
            Schema::table('projects', function (Blueprint $table) {
                $table->dropForeign(['phase_id']);
                $table->dropColumn('phase_id');
            });
        }
    }
};
