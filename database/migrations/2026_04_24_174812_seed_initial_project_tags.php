<?php

use App\Models\Tag;
use Illuminate\Database\Migrations\Migration;
use Spatie\Activitylog\Facades\Activity;

return new class extends Migration
{
    public function up(): void
    {
        $initialProjectTags = [
            [
                'name' => 'Acadêmico',
                'type' => 'projects',
                'color' => 'badge-info',
                'description' => 'Projetos de cunho acadêmico, como pesquisas e trabalhos.',
            ],
            [
                'name' => 'Desenvolvimento',
                'type' => 'projects',
                'color' => 'badge-success',
                'description' => 'Desenvolvimento de software.',
            ],
            [
                'name' => 'Infraestrutura',
                'type' => 'projects',
                'color' => 'badge-warning',
                'description' => 'Configuração de servidores, Docker e CI/CD.',
            ],
            [
                'name' => 'Gestão',
                'type' => 'projects',
                'color' => 'badge-dark',
                'description' => 'Projetos de planejamento e coordenação de equipes.',
            ],
        ];

        Activity::withoutLogs(function () use ($initialProjectTags): void {
            foreach ($initialProjectTags as $tagData) {
                Tag::firstOrCreate(
                    ['name' => $tagData['name'], 'type' => $tagData['type']],
                    ['color' => $tagData['color'], 'description' => $tagData['description']]
                );
            }
        });
    }

    public function down(): void
    {
        Activity::withoutLogs(function (): void {
            Tag::where('type', 'projects')->delete();
        });
    }
};
