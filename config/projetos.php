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
        ],

        'duplicable' => [
            'project' => App\Models\Project::class,
            'task'    => App\Models\Task::class,
            'meeting' => App\Models\Meeting::class,
        ],

        'mention' => [
            'source' => [
                'project'      => App\Models\Project::class,
                'task'         => App\Models\Task::class,
                'meeting'      => App\Models\Meeting::class,
                'meeting_item' => App\Models\MeetingItem::class,
                'comment'      => App\Models\Comment::class,
            ],
            'target' => [
                'user'    => App\Models\User::class,
                'project' => App\Models\Project::class,
                'task'    => App\Models\Task::class,
                'meeting' => App\Models\Meeting::class,
            ],
        ],
    ],

    'watching' => [
        'digest_minutes' => 5,
    ],

    /*
    |--------------------------------------------------------------------------
    | Activity Log
    |--------------------------------------------------------------------------
    | Centraliza a configuracao do spatie/laravel-activitylog.
    */
    'activitylog' => [
        'enabled' => env('ACTIVITY_LOGGER_ENABLED', true),
        'default_log_name' => 'uncategorized',
        'default_auth_driver' => null,
        'activity_model' => App\Models\ActivityLog::class,
        'table_name' => 'activity_log',
        'database_connection' => null,
        'subject_returns_soft_deleted_models' => true,
        'delete_records_older_than_days' => 365,
        'retention_policies' => [
            'project' => 365,
            'task' => 365,
            'meeting' => 365,
            'meeting_item' => 365,
            'comment' => 365,
            'module' => 365,
            'project_type' => 365,
            'tag' => 365,
            'uncategorized' => 365,
            // Por hora, todas as retention_policies têm o mesmo valor (365), Isso é um placeholder e deve ser revisado antes de ir para produção
        ],
    ],
];
