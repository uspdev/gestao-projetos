<?php

use App\Models\Tag;
use Illuminate\Database\Migrations\Migration;
use Spatie\Activitylog\Facades\Activity;

return new class extends Migration
{
    public function up(): void
    {
        $initialTaskTags = [
            [
                'name' => 'Correção',
                'type' => 'tasks',
                'color' => 'badge-danger',
                'description' => 'Correção de bugs ou falhas críticas.',
            ],
            [
                'name' => 'Funcionalidade',
                'type' => 'tasks',
                'color' => 'badge-primary',
                'description' => 'Nova funcionalidade ou requisito.',
            ],
            [
                'name' => 'Teste',
                'type' => 'tasks',
                'color' => 'badge-info',
                'description' => 'Criação ou manutenção de testes.',
            ],
            [
                'name' => 'Documentação',
                'type' => 'tasks',
                'color' => 'badge-dark',
                'description' => 'Atualização de documentação.',
            ],
            [
                'name' => 'Refatoração',
                'type' => 'tasks',
                'color' => 'badge-secondary',
                'description' => 'Melhoria de código sem alteração de comportamento.',
            ]
        ];

        // Em bancos novos ou reconstruídos, estas tags são criadas antes da tabela activity_log. Não impacta banco já criado, como o existente em prod.
        Activity::withoutLogs(function () use ($initialTaskTags): void {
            foreach ($initialTaskTags as $tagData) {
                Tag::firstOrCreate(
                    ['name' => $tagData['name'], 'type' => $tagData['type']],
                    ['color' => $tagData['color'], 'description' => $tagData['description']]
                );
            }
        });
    }

    public function down(): void
    {
        // A reversão também deve funcionar quando a tabela activity_log ainda não existe.
        Activity::withoutLogs(function (): void {
            Tag::whereIn('name', ['Correção', 'Funcionalidade'])->where('type', 'tasks')->delete();
        });
    }
};
