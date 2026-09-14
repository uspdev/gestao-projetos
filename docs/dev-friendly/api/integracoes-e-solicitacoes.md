# API de Integrações e Solicitações

Este documento é o contrato HTTP da primeira versão da API usada por Sistemas
clientes. Ele descreve a instalação da infraestrutura de Chaves de API, a
autenticação, os recursos expostos e o acompanhamento de Solicitações.

Para uma verificação objetiva da integração local, consulte o
[teste manual rápido da API](teste-manual-integracoes.md).

A API não representa usuários do sistema externo. O **Sistema cliente** é o
único ator externo reconhecido e cada uma de suas credenciais permanece
limitada ao **Projeto de escopo da Chave**.

## Requisitos, instalação e configuração

### Requisitos

- PHP 8.3 ou superior compatível com a restrição `^8.3`;
- Laravel 12;
- Composer;
- `uspdev/api-keys` na série compatível `^0.1`.

Para integrar o package pela primeira vez em uma aplicação hospedeira, a ordem
é:

```sh
composer require uspdev/api-keys:^0.1
php artisan vendor:publish --tag=api-keys-config
php artisan vendor:publish --tag=api-keys-migrations
php artisan migrate
```

A publicação de `api-keys-views` é opcional e só é necessária para sobrescrever
as views do package. Esta aplicação usa diretamente o componente fornecido e
não publica essa tag.

Neste repositório, a dependência, `config/api-keys.php` e a migration
`2026_07_13_000000_create_uspdev_api_keys_table.php` já estão versionadas. Uma
instalação a partir do repositório deve usar `composer install` e não precisa
republicar esses arquivos.

### Owner, papéis e finalidades

O owner configurado é o model `App\Models\ClientSystem`, sob o alias estável
`client-system`:

```php
'owners' => [
    'client-system' => App\Models\ClientSystem::class,
],
```

O model usa os traits `HasApiKeys` e `HasApiAbilities`. A resolução das
abilities é explícita:

| Papel | Abilities |
| --- | --- |
| `viewer` | `projects.read`, `tasks.read`, `requests.read` |
| `contributor` | `projects.read`, `tasks.read`, `requests.read`, `requests.create` |

Um papel desconhecido não concede abilities. Não há papel `administrator` nem
wildcard `*` para Chaves de API.

As finalidades disponíveis são `integration` (“Integração”) e `ai` (“IA”). O
campo `purpose` é somente metadado da credencial: ele não concede abilities,
não escolhe rotas e não altera payloads ou respostas. O acesso é determinado
pelo papel da Chave de API e pela ability declarada na rota.

Administradores locais do Projeto e administradores globais da aplicação
administram Sistemas clientes e credenciais pela interface web. A página do
Sistema cliente incorpora o gerenciador do package, que emite, renova e revoga
chaves. O token completo é mostrado somente na emissão ou renovação. Renovar
cria outra credencial para o mesmo Sistema cliente e revoga a anterior; revogar
interrompe imediatamente o acesso. A expiração é opcional e, quando informada,
deve estar no futuro.

## Autenticação e limites

Envie a credencial exclusivamente no cabeçalho HTTP:

```http
Authorization: Bearer <CHAVE_DE_API>
Accept: application/json
```

O parâmetro de query `api_key` está desabilitado. Uma chave incluída na URL não
autentica a requisição, porque URLs podem vazar por logs, histórico, proxies e
ferramentas de monitoramento. O backend do Sistema cliente deve guardar a
credencial e não a entregar ao navegador ou ao usuário final.

O grupo `/api` aceita inicialmente 60 requisições por minuto por endereço IP.
Não há cota individual por Chave de API: Sistemas clientes que saem pelo mesmo
IP compartilham o limite e recebem `429 Too Many Requests` quando ele é
excedido.

As rotas não têm um segmento de versão inicial. Nos exemplos abaixo,
`https://projetos.example` representa a URL da instalação e
`portal-de-servicos` representa o slug do Projeto.

## Escopo e matriz de rotas

O parâmetro `{project}` é sempre o slug do Projeto. `{task}` e `{request}` são
identificadores numéricos. A aplicação confere o escopo além da ability:

- a Chave de API deve pertencer a um Sistema cliente vinculado exatamente ao
  Projeto indicado;
- a Tarefa deve pertencer ao Projeto da URL;
- a Solicitação deve pertencer simultaneamente ao Projeto e ao Sistema cliente
  autenticado.

Divergências retornam `404 Not Found` e não confirmam a existência de recursos
alheios.

| Método | Rota | Ability | Resultado normal |
| --- | --- | --- | --- |
| `GET` | `/api/projects/{project}` | `projects.read` | `200 OK` |
| `GET` | `/api/projects/{project}/tasks` | `tasks.read` | `200 OK` |
| `GET` | `/api/projects/{project}/tasks/{task}` | `tasks.read` | `200 OK` |
| `POST` | `/api/projects/{project}/requests` | `requests.create` | `201 Created` |
| `GET` | `/api/projects/{project}/requests` | `requests.read` | `200 OK` |
| `GET` | `/api/projects/{project}/requests/{request}` | `requests.read` | `200 OK` |

Não existe rota externa para criar Tarefas diretamente nem para alterar,
excluir, aceitar ou rejeitar Solicitações. A avaliação é uma ação interna de
um Avaliador da Solicitação com vínculo local no Projeto.

## Representações

Recursos individuais são envolvidos pela chave `data`. Datas e horários usam
ISO 8601; datas sem horário usam `YYYY-MM-DD`. Campos opcionais sem valor são
retornados como `null`, em vez de serem omitidos.

### Projeto

`GET /api/projects/{project}` devolve o contexto do Projeto, inclusive o estado
do módulo de Tarefas:

```sh
curl --request GET \
  --header "Authorization: Bearer ${API_KEY}" \
  --header "Accept: application/json" \
  "https://projetos.example/api/projects/portal-de-servicos"
```

```json
{
  "data": {
    "id": 42,
    "slug": "portal-de-servicos",
    "name": "Portal de serviços",
    "description": "## Contexto\n\nDescrição em **Markdown**.",
    "status": {
      "value": "ACTIVE",
      "label": "Ativo"
    },
    "type": {
      "id": 3,
      "slug": "desenvolvimento",
      "name": "Desenvolvimento"
    },
    "phase": {
      "id": 7,
      "slug": "production",
      "name": "Produção"
    },
    "parent": {
      "id": 10,
      "slug": "programa-institucional",
      "name": "Programa institucional"
    },
    "tags": [
      {
        "id": 5,
        "name": "Prioridade institucional",
        "slug": "prioridade-institucional"
      }
    ],
    "modules": {
      "enabled": ["tasks", "meetings"],
      "tasks_enabled": true
    },
    "web_url": "https://projetos.example/projects/portal-de-servicos",
    "created_at": "2026-08-30T09:00:00.000000Z",
    "updated_at": "2026-09-10T15:30:00.000000Z"
  }
}
```

`type`, `phase` e `parent` podem ser `null`. A descrição é o Markdown original,
sem conversão para HTML. A resposta não expõe membros, papéis humanos,
visibilidade, herança de permissões nem autoria e auditoria internas.

Os estados atuais do Projeto são `DRAFT` (Rascunho), `PLANNED` (Planejado),
`ACTIVE` (Ativo), `HOLD` (Em Espera), `COMPLETED` (Concluído), `CANCELLED`
(Cancelado) e `ARCHIVED` (Arquivado). `modules.enabled` contém os slugs dos
módulos habilitados; consumidores que dependem de Tarefas devem verificar
também o booleano estável `modules.tasks_enabled`.

### Tarefa

A listagem e o detalhe usam a mesma representação integral de Tarefa:

```json
{
  "id": 81,
  "title": "Documentar integração",
  "description": "## Contexto integral\n\nDescrição em **Markdown**.",
  "status": {
    "value": "IN_PROGRESS",
    "label": "Em Andamento"
  },
  "priority": {
    "value": 2,
    "label": "Alta"
  },
  "start_date": "2026-09-01",
  "due_date": "2026-09-30",
  "completed_at": null,
  "created_at": "2026-08-30T09:00:00.000000Z",
  "updated_at": "2026-09-10T15:30:00.000000Z",
  "assignees": [
    {"name": "Ana Responsável"}
  ],
  "tags": [
    {"id": 9, "name": "Integração", "slug": "integracao"}
  ],
  "web_url": "https://projetos.example/tasks/81"
}
```

`description` e `priority` podem ser `null`. A descrição, quando existente, é
integral e permanece em Markdown. Responsáveis são expostos somente pelo nome;
e-mail, número USP e demais atributos pessoais não integram o contrato. A API
também não expõe comentários, Arquivos, Links, Menções, auditoria nem o vínculo
de origem com uma Solicitação.

Os valores de estado aceitos pelo filtro e seus rótulos atuais são `NEW`
(Nova), `ASSIGNED` (Atribuída), `IN_PROGRESS` (Em Andamento), `IN_REVIEW` (Em
Revisão), `HOLD` (Em Espera) e `DONE` (Concluída). Os valores de prioridade
presentes nas respostas são `1` (Urgente), `2` (Alta), `3` (Média) e `4`
(Baixa).

### Solicitação

A criação, a listagem e o detalhe usam a mesma representação integral. Uma
Solicitação pendente tem `response`, `evaluated_at` e `task` nulos:

```json
{
  "id": 93,
  "title": "Corrigir integração",
  "description": "Primeira linha\nSegunda linha",
  "source_url": "https://cliente.example/solicitacoes/42",
  "status": {
    "value": "pending",
    "label": "Pendente"
  },
  "response": null,
  "created_at": "2026-09-14T10:00:00.000000Z",
  "updated_at": "2026-09-14T10:00:00.000000Z",
  "evaluated_at": null,
  "task": null,
  "web_url": "https://projetos.example/projects/portal-de-servicos/requests/93"
}
```

Os estados possíveis são `pending` (Pendente), `accepted` (Aceita) e
`rejected` (Rejeitada). Eles são finais após a avaliação. O Avaliador da
Solicitação é persistido internamente, mas sua identidade não é exposta.

Uma Solicitação aceita inclui a Resposta à Solicitação, quando fornecida, e um
resumo da Tarefa resultante:

```json
{
  "status": {"value": "accepted", "label": "Aceita"},
  "response": "A proposta foi incorporada ao planejamento.",
  "evaluated_at": "2026-09-15T14:20:00.000000Z",
  "task": {
    "id": 105,
    "title": "Tarefa revisada pela equipe",
    "web_url": "https://projetos.example/tasks/105"
  }
}
```

O trecho acima destaca os campos que mudam; os demais campos da representação
integral continuam presentes. Uma Solicitação rejeitada traz o estado
`rejected`, uma `response` obrigatória e `task: null`.

Os campos `web_url` apontam para a interface interna e não concedem acesso por
si próprios. Um usuário humano ainda precisa se autenticar e possuir a
permissão local correspondente para abrir o recurso.

## Tarefas: listagem e detalhe

### Listagem

```sh
curl --request GET \
  --header "Authorization: Bearer ${API_KEY}" \
  --header "Accept: application/json" \
  "https://projetos.example/api/projects/portal-de-servicos/tasks?status[]=IN_PROGRESS&status[]=DONE&per_page=20&page=1"
```

Parâmetros opcionais:

| Parâmetro | Regra |
| --- | --- |
| `status` | Um valor ou array não vazio com estados atuais de Tarefa |
| `per_page` | Inteiro de 1 a 100; padrão 20 |
| `page` | Página solicitada pela paginação Laravel |

A listagem contém todas as Tarefas não excluídas, inclusive concluídas, e é
ordenada por `updated_at` decrescente e depois por `id` decrescente. Não há
opção de desabilitar a paginação.

### Detalhe

```sh
curl --request GET \
  --header "Authorization: Bearer ${API_KEY}" \
  --header "Accept: application/json" \
  "https://projetos.example/api/projects/portal-de-servicos/tasks/81"
```

O retorno é `{"data": <Tarefa>}` com a representação integral descrita acima.

## Solicitações: criação, listagem e detalhe

### Criação

Somente o papel `contributor`, que possui `requests.create`, pode enviar uma
Solicitação:

```sh
curl --request POST \
  --header "Authorization: Bearer ${API_KEY}" \
  --header "Accept: application/json" \
  --header "Content-Type: application/json" \
  --data '{
    "title": "Corrigir integração",
    "description": "Primeira linha\nSegunda linha",
    "source_url": "https://cliente.example/solicitacoes/42"
  }' \
  "https://projetos.example/api/projects/portal-de-servicos/requests"
```

Regras do payload:

| Campo | Obrigatório | Regra |
| --- | --- | --- |
| `title` | sim | texto de 3 a 120 caracteres, não vazio após trim |
| `description` | sim | texto simples de até 10.000 caracteres, não vazio após trim |
| `source_url` | não | URL absoluta `http` ou `https`, com até 2.048 caracteres |

Título e descrição são aparados nas extremidades, sem truncamento. A descrição
é preservada como texto simples com quebras de linha; não produz HTML,
Markdown nem Menções. `source_url` é somente armazenada e apresentada: o
servidor não faz requisições ao endereço.

Projeto, Sistema cliente, estado, resposta, avaliação e Tarefa resultante são
controlados pelo servidor. A presença dos campos `project_id`, `project`,
`client_system_id`, `client_system`, `status`, `response`, `evaluated_by`,
`evaluator`, `evaluated_at`, `task_id` ou `task` produz `422 Unprocessable
Content`, mesmo se o valor enviado for `null`.

Uma criação válida retorna `201 Created`, a representação integral com estado
`pending` e o cabeçalho `Location` contendo a URL absoluta do detalhe
canônico, por exemplo:

```http
HTTP/1.1 201 Created
Location: https://projetos.example/api/projects/portal-de-servicos/requests/93
Content-Type: application/json
```

### Listagem

```sh
curl --request GET \
  --header "Authorization: Bearer ${API_KEY}" \
  --header "Accept: application/json" \
  "https://projetos.example/api/projects/portal-de-servicos/requests?status=pending&per_page=20&page=1"
```

`status` aceita um valor ou array não vazio formado por `pending`, `accepted`
e `rejected`. `per_page` aceita de 1 a 100 e usa 20 por padrão. A listagem
inclui somente Solicitações do Sistema cliente autenticado, inclui todos os
estados quando não há filtro e ordena por `created_at` decrescente e depois por
`id` decrescente.

### Detalhe e acompanhamento

```sh
curl --request GET \
  --header "Authorization: Bearer ${API_KEY}" \
  --header "Accept: application/json" \
  "https://projetos.example/api/projects/portal-de-servicos/requests/93"
```

O acompanhamento é feito por consulta periódica à listagem ou ao detalhe. Não
há webhook nem callback. A criação também não oferece chave de idempotência ou
deduplicação automática: um retry depois de timeout pode criar Solicitações
duplicadas. O Sistema cliente deve guardar o ID ou o `Location` recebido e,
quando não souber se a primeira tentativa foi persistida, considerar essa
possibilidade antes de reenviar.

## Formato das listagens

As listagens de Tarefas e Solicitações seguem a paginação de recursos do
Laravel:

```json
{
  "data": [
    {"id": 93}
  ],
  "links": {
    "first": "https://projetos.example/api/projects/portal-de-servicos/requests?page=1",
    "last": "https://projetos.example/api/projects/portal-de-servicos/requests?page=3",
    "prev": null,
    "next": "https://projetos.example/api/projects/portal-de-servicos/requests?page=2"
  },
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 3,
    "links": [],
    "path": "https://projetos.example/api/projects/portal-de-servicos/requests",
    "per_page": 20,
    "to": 20,
    "total": 45
  }
}
```

Os objetos em `data` são sempre as representações integrais descritas neste
documento. Os filtros atuais são mantidos nos links gerados pela paginação.

## Módulo de Tarefas desabilitado

O estado pode ser descoberto em `data.modules.tasks_enabled` na leitura do
Projeto.

| Operação | Com o módulo desabilitado |
| --- | --- |
| Ler o Projeto | disponível; informa `tasks_enabled: false` |
| Listar ou consultar Tarefas | `409 Conflict` |
| Criar uma Solicitação | `409 Conflict` |
| Listar ou consultar Solicitações existentes | disponível |
| Rejeitar pela interface interna | disponível ao Avaliador da Solicitação |
| Aceitar pela interface interna | indisponível até reabilitar o módulo |

O corpo do conflito externo é estável:

```json
{
  "message": "O módulo de Tarefas está desabilitado neste Projeto.",
  "code": "tasks_module_disabled"
}
```

## Erros HTTP

Não há envelope global de erros. Trate cada status conforme seu significado:

| Status | Quando ocorre | Formato relevante |
| --- | --- | --- |
| `401 Unauthorized` | Bearer ausente, malformado, inválido, expirado ou revogado | `{"message":"Unauthenticated."}` |
| `403 Forbidden` | chave válida sem a ability exigida | `{"message":"Forbidden."}` |
| `404 Not Found` | slug ou ID inexistente, Projeto fora do escopo, Tarefa de outro Projeto ou Solicitação de outro Sistema cliente | objeto com `message` |
| `409 Conflict` | operação incompatível com o módulo de Tarefas desabilitado | objeto com `message` e `code` |
| `422 Unprocessable Content` | payload, paginação ou filtro inválido | formato de validação Laravel com `message` e `errors` |
| `429 Too Many Requests` | cota compartilhada do IP excedida | formato convencional do limitador Laravel |

Exemplo resumido de validação:

```json
{
  "message": "The given data was invalid.",
  "errors": {
    "per_page": ["..."]
  }
}
```

Não use o texto localizado de `422` ou `429` como identificador técnico; use o
status e, em validação, as chaves de `errors`. O conflito do módulo é a exceção
que fornece o código técnico estável `tasks_module_disabled`.

## Ciclo da Solicitação e isolamento

Toda Solicitação começa pendente. Um administrador ou contribuidor com vínculo
local no Projeto pode avaliá-la na interface interna. O Avaliador da
Solicitação pode:

- rejeitar a proposta com uma Resposta à Solicitação obrigatória; ou
- aceitar por meio do formulário normal de criação de Tarefa, revisando todos
  os dados antes de incorporá-la ao trabalho.

Aceitação e rejeição são terminais. A aceitação, a criação da Tarefa, a autoria
do usuário local e o vínculo ocorrem na mesma transação, e uma Solicitação pode
originar no máximo uma Tarefa. O pedido original e o Sistema cliente permanecem
preservados para auditoria. Abrir ou cancelar o formulário e erros de validação
mantêm a Solicitação pendente.

Credenciais renovadas continuam pertencendo ao mesmo Sistema cliente e,
portanto, consultam o mesmo histórico. Sistemas clientes diferentes no mesmo
Projeto não veem as Solicitações uns dos outros. O Sistema cliente nunca se
torna membro humano do Projeto e não recebe permissões por herança.

## Slug e estabilidade das URLs

Alterar o slug do Projeto quebra deliberadamente todas as URLs anteriores da
API. A Chave e o vínculo do Sistema cliente continuam válidos, mas o consumidor
precisa passar a usar o novo slug; não há redirecionamento nem alias do valor
antigo. A interface administrativa destaca esse impacto quando o Projeto tem
Sistemas clientes.

## Ordem operacional de implantação

Esta seção registra a ordem; não autoriza nem executa uma implantação em
produção.

Para publicar uma revisão desta aplicação que introduza a integração:

1. confirme que os ambientes de desenvolvimento, integração e produção usam
   PHP 8.3 ou versão compatível com `^8.3`;
2. faça o procedimento de backup e manutenção definido para o ambiente;
3. disponibilize a revisão da aplicação, incluindo `composer.lock`,
   `config/api-keys.php` e as migrations versionadas;
4. instale as dependências da revisão;
5. aplique as migrations;
6. verifique o estado das migrations e execute a suíte e as sondagens HTTP do
   ambiente antes de emitir credenciais de Sistemas clientes.

Os comandos correspondentes às etapas da aplicação são:

```sh
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan migrate:status
php artisan test
php artisan route:list --path=api
```

As migrations relevantes são ordenadas pelos timestamps: primeiro
`2026_07_13_000000_create_uspdev_api_keys_table.php`, depois
`2026_09_14_000000_create_client_systems_table.php` e por fim
`2026_09_14_010000_create_project_requests_table.php`. Não execute a publicação
das tags do package sobre arquivos já versionados durante cada deploy e não
execute migrations de produção a partir deste documento sem o procedimento de
mudança aprovado para o ambiente.
