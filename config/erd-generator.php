<?php

return [

    /*
     * All models in these directories will be scanned for ER diagram generation.
     * By default, the `app` directory will be scanned recursively for models.
     */
    'directories' => [
        base_path('app' . DIRECTORY_SEPARATOR . 'Models'),
    ],

    /*
     * If you want to ignore complete models or certain relations of a specific model,
     * you can specify them here.
     * To ignore a model completely, just add the fully qualified classname.
     * To ignore only a certain relation of a model, enter the classname as the key
     * and an array of relation names to ignore.
     */
    'ignore' => [
        // Ignore as tabelas de infraestrutura e pacotes
        App\Models\User::class, // (Opcional) Ignore se tiver muitas ligações e poluir o centro
        App\Models\ActivityLog::class,
        Spatie\Activitylog\Models\Activity::class,
        Spatie\Permission\Models\Role::class,
        Spatie\Permission\Models\Permission::class,
        Laravel\Sanctum\PersonalAccessToken::class,
        // E outras tabelas que você julgar "metadados"
    ],

    /*
     * If you want to see only specific models, specify them here using fully qualified
     * classnames.
     *
     * Note: that if this array is filled, the 'ignore' array will not be used.
    */
    'whitelist' => [
        // App\User::class,
        // App\Post::class,
    ],

    /*
     * If you want to rename models in the generated diagram, you can specify aliases
     * for them here.
     */
    'aliases' => [
        // User::class => 'CustomUser',
    ],

    /*
     * If true, all directories specified will be scanned recursively for models.
     * Set this to false if you prefer to explicitly define each directory that should
     * be scanned for models.
     */
    'recursive' => true,

    /*
     * The generator will automatically try to look up the model specific columns
     * and add them to the generated output. If you do not wish to use this
     * feature, you can disable it here.
     */
    'use_db_schema' => true,

    /*
     * This setting toggles weather the column types (VARCHAR, INT, TEXT, etc.)
     * should be visible on the generated diagram. This option requires
     * 'use_db_schema' to be set to true.
     */
    'use_column_types' => true,

    /*
     * If you want to ignore specific columns in specific tables you can specify them here.
     * Use the dot notation to specify the table and the column : table.column
     * This option only apply when 'use_db_schema' is set to true.
    */
    'ignore_columns' => [
        // 'users.lastname',
        // 'posts.description',
    ],

    /*
     * These colors will be used in the table representation for each entity in
     * your graph.
     */
    'table' => [
        'header_background_color' => '#1F2937',
        'header_font_color' => '#FFFFFF',
        'row_background_color' => '#FFFFFF',
        'row_font_color' => '#374151',
    ],

    'attributes' => [
        'primary_key' => [
            'font_color' => '#EF4444', // Vermelho para PK
        ],
        'foreign_key' => [
            'font_color' => '#F59E0B', // Laranja para FK
        ],
    ],

    /*
     * Here you can define all the available Graphviz attributes that should be applied to your graph,
     * to its nodes and to the edge (the connection between the nodes). Depending on the size of
     * your diagram, different settings might produce better looking results for you.
     *
     * See http://www.graphviz.org/doc/info/attrs.html#d:label for a full list of attributes.
     */
    'graph' => [
        'style' => 'filled',
        'bgcolor' => '#F7F7F7',
        'fontsize' => 12,
        'labelloc' => 't',
        'concentrate' => true,
        'splines' => 'polyline',
        'overlap' => false,
        'nodesep' => 1,
        'rankdir' => 'LR',
        'pad' => 0.5,
        'ranksep' => 2,
        'esep' => true,
        'fontname' => 'Helvetica Neue'
    ],

    'node' => [
        'margin' => 0,
        'shape' => 'rectangle',
        'fontname' => 'Helvetica Neue'
    ],

    'edge' => [
        'color' => '#003049',
        'penwidth' => 1.8,
        'fontname' => 'Helvetica Neue'
    ],

    'relations' => [
        'HasOne' => [
            'dir' => 'both',
            'color' => '#D62828',
            'arrowhead' => 'tee',
            'arrowtail' => 'none',
        ],
        'BelongsTo' => [
            'dir' => 'both',
            'color' => '#F77F00',
            'arrowhead' => 'tee',
            'arrowtail' => 'crow',
        ],
        'HasMany' => [
            'dir' => 'both',
            'color' => '#FCBF49',
            'arrowhead' => 'crow',
            'arrowtail' => 'none',
        ],
    ]

];
