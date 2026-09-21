<?php

/*
|--------------------------------------------------------------------------
| Configuração do package de API Keys
|--------------------------------------------------------------------------
| Estes valores definem como a aplicação hospedeira cria, autentica e
| gerencia API Keys. Ao publicar este arquivo, ajuste somente as opções que
| façam parte do domínio da aplicação.
*/

// Middlewares padrão das rotas de criação, renovação e revogação. O grupo
// 'web' habilita sessão e proteção CSRF; 'auth' exige um usuário autenticado.
$middleware = [
    'web',
    'auth',
];

// Finalidades oferecidas pelo componente Blade. A finalidade é armazenada
// como metadado; o package não altera respostas ou regras de negócio por ela.
$purposes = [
    'integration' => 'Integração',
    'ai' => 'IA',
];

// Papéis oferecidos na emissão da chave. O papel não concede acesso sozinho:
// o método abilities() do owner resolve as abilities efetivas de cada papel.
$roles = [
    'viewer' => 'Visualizador',
    'contributor' => 'Contribuidor',
];

return [
    // Models que podem possuir API Keys. A chave do array é o alias estável
    // usado nas rotas de gerenciamento; o valor é a classe Eloquent do owner.
    // Exemplo: 'project' => App\\Models\\Project::class.
    'owners' => [
        'client-system' => App\Models\ClientSystem::class,
        'project' => App\Models\Project::class,
    ],

    // Prefixo das rotas de gerenciamento fornecidas pelo package, incluindo
    // as operações de criação, renovação e revogação de uma chave.
    'prefix' => 'api-keys',

    // Prefixo textual de toda credencial emitida. O formato público é
    // gpp_PREFIXO.SEGREDO e somente o hash do segredo é persistido.
    'credential_prefix' => env('API_KEYS_CREDENTIAL_PREFIX', 'gpp'),

    // Quantidade de caracteres do prefixo público que identifica a chave no
    // banco sem revelar o segredo. O prefixo isolado não autentica a chave.
    'public_prefix_length' => (int) env('API_KEYS_PUBLIC_PREFIX_LENGTH', 6),

    // Quantidade mínima de bytes aleatórios usados para gerar o segredo. O
    // token completo fica disponível somente no momento de sua entrega.
    'secret_bytes' => (int) env('API_KEYS_SECRET_BYTES', 32),

    // Integração entre o middleware do package e as rotas de negócio da
    // aplicação hospedeira protegidas por API Key.
    'middleware' => [
        // Alias usado nas rotas, sempre acompanhado de ao menos uma ability.
        // Exemplo: Route::middleware('uspdevApiKeys:tasks.read').
        'alias' => env('API_KEYS_MIDDLEWARE_ALIAS', 'uspdevApiKeys'),

        // Nome do atributo adicionado ao Request com a API Key autenticada.
        // Controllers podem obtê-la por $request->attributes->get('apiKey').
        'request_attribute' => env('API_KEYS_REQUEST_ATTRIBUTE', 'apiKey'),
    ],

    // Fallback para clientes que não conseguem enviar Authorization: Bearer.
    // Mantenha desabilitado quando não for necessário: URLs podem aparecer em
    // logs, históricos do navegador, proxies e ferramentas de monitoramento.
    'query_parameter' => [
        // Habilita o recebimento do token completo pela query string.
        'enabled' => false,

        // Nome do parâmetro aceito quando o fallback estiver habilitado.
        'name' => env('API_KEYS_QUERY_PARAMETER_NAME', 'api_key'),
    ],

    // Proteção das rotas de gerenciamento do package. Estas opções controlam
    // a interface administrativa, não as APIs de negócio autenticadas por chave.
    'management' => [
        // Stack aplicada às operações de criação, renovação e revogação.
        'middleware' => $middleware,

        // Ability do Laravel usada para decidir quem gerencia as chaves de
        // cada owner. A Policy ou o Gate pertence à aplicação hospedeira.
        'ability' => 'manageApiKeys',
    ],

    // Valores exibidos nos campos de seleção do componente Blade. As chaves
    // são persistidas na API Key e os textos são apresentados ao usuário.
    'interface' => [
        // Finalidades que a aplicação permite selecionar ao emitir uma chave.
        'purposes' => $purposes,

        // Papéis que o owner deve reconhecer em seu método abilities().
        'roles' => $roles,
    ],
];
