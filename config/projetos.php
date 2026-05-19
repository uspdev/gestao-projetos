<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Palavras Reservadas (Slug Blocklist)
    |--------------------------------------------------------------------------
    | Lista de palavras que nao podem ser utilizadas como slug de projetos
    | para evitar conflito com as rotas do sistema.
    */
    'slug_blocklist' => [
        'create', 'edit', 'tasks', 'members', 'status', 'admin', 'api',
        'dashboard', 'painel', 'config', 'settings', 'index', 'show',
        'store', 'update', 'destroy',
    ],

    /*
    |--------------------------------------------------------------------------
    | Mapeamento Polimorfico (Morphs)
    |--------------------------------------------------------------------------
    | Define os aliases utilizados no banco de dados para os relacionamentos
    | polimorficos. Isso evita o acoplamento com o FQCN (Fully Qualified Class Name).
    */
    'morphs' => [
        'discussable' => [
            'project' => App\Models\Project::class,
            'task'    => App\Models\Task::class,
        ],

        'commentable' => [
            'project'      => App\Models\Project::class,
            'task'         => App\Models\Task::class,
            'meeting'      => App\Models\Meeting::class,
            'meeting_item' => App\Models\MeetingItem::class,
        ],
    ],
];
