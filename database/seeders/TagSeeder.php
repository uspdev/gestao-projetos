<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $initialTaskTags = [
            [
                'name' => 'Fix',
                'type' => 'tasks',
                'color' => 'badge-danger',
                'description' => 'Correção de bugs ou falhas críticas.',
            ],
            [
                'name' => 'Feature',
                'type' => 'tasks',
                'color' => 'badge-primary',
                'description' => 'Nova funcionalidade ou requisito.',
            ],
            [
                'name' => 'Test',
                'type' => 'tasks',
                'color' => 'badge-info',
                'description' => 'Criação ou manutenção de testes.',
            ],
            [
                'name' => 'Doc',
                'type' => 'tasks',
                'color' => 'badge-dark',
                'description' => 'Atualização de documentação.',
            ],
            [
                'name' => 'Refactor',
                'type' => 'tasks',
                'color' => 'badge-secondary',
                'description' => 'Melhoria de código sem alteração de comportamento.',
            ],
        ];

        foreach ($initialTaskTags as $tagData) {
            Tag::firstOrCreate(
                ['name' => $tagData['name'], 'type' => $tagData['type']],
                ['color' => $tagData['color'], 'description' => $tagData['description']]
            );
        }
    }
}