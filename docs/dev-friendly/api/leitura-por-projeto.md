# API de leitura por Projeto

Este guia é o contrato público para consultar um Projeto, suas Reuniões,
Tarefas e Arquivos. A API de negócio oferece somente operações `GET`. Ela não
cria, altera ou exclui trabalho.

A abertura e a triagem de demandas pertencem ao Chamados, que já oferece
acompanhamento, comentários e Arquivos para esse fluxo no contexto USP. Manter
o mesmo ciclo neste sistema criaria duas filas concorrentes; por isso, o
Gestão de Projetos não recebe Solicitações nem cria Tarefas pela API. A criação
manual de Tarefas permanece disponível na interface web.

## URL, autenticação e escopo

Todas as rotas ficam abaixo de `/api/projects/{project}`, em que `{project}` é
o slug explícito do Projeto. Envie a Chave de API somente pelo cabeçalho:

```http
Authorization: Bearer <CHAVE_DE_API>
Accept: application/json
```

O fallback por `api_key` está desabilitado para evitar que credenciais sejam
registradas em URLs, históricos e logs. O download também exige o cabeçalho
Bearer.

A Chave de API pertence diretamente a um Projeto. Em cada requisição, a
aplicação verifica separadamente:

1. se a chave possui a ability exigida; e
2. se o Projeto e o recurso pedidos pertencem ao escopo dessa chave.

Um recurso inexistente, excluído logicamente ou fora do escopo responde `404`
sem confirmar sua existência em outro Projeto. Uma referência a outro Projeto
encontrada na Pauta de uma Reunião não amplia o escopo da chave.

Os papéis `viewer` e `contributor` concedem temporariamente as mesmas abilities
de leitura: `projects.read`, `meetings.read`, `tasks.read` e `files.read`.
Nenhum papel concede wildcard, e um papel desconhecido não concede abilities.
`purpose` é somente metadado: as finalidades `integration` e `ai` não alteram
permissões nem respostas.

Administradores diretamente vinculados ao Projeto emitem, renovam e revogam
as chaves pelo gerenciador disponível nas configurações do Projeto. Essas são
operações administrativas da interface web e não transformam a API de negócio
em uma API de escrita.

## Rotas

| Método e rota | Ability | Resposta |
| --- | --- | --- |
| `GET /api/projects/{project}` | `projects.read` | detalhe JSON do Projeto |
| `GET /api/projects/{project}/meetings` | `meetings.read` | coleção de Reuniões |
| `GET /api/projects/{project}/meetings/{meeting}` | `meetings.read` | detalhe JSON da Reunião |
| `GET /api/projects/{project}/tasks` | `tasks.read` | coleção de Tarefas |
| `GET /api/projects/{project}/tasks/{task}` | `tasks.read` | detalhe JSON da Tarefa |
| `GET /api/projects/{project}/files` | `files.read` | coleção de Arquivos |
| `GET /api/projects/{project}/files/{uuid}` | `files.read` | download do Arquivo |

`{meeting}` e `{task}` são identificadores numéricos. `{uuid}` é o UUID
publicado na coleção de Arquivos. Não há segmento de versão inicial.

## Projeto

O detalhe do Projeto reúne todas as informações disponibilizadas pela
interface a um visualizador. Retorna `id`, `slug`, `name`, a `description`
integral em Markdown, `status`, `visibility` e `permission_inheritance` com
valor e rótulo, `type`, `phase`, `parent`, `tags`, `modules_enabled`, `members`,
`comments`, `files`, `links`, `incoming_mentions`, `agenda_meetings`,
`subprojects`, `web_url` e timestamps.

`modules_enabled` contém somente os módulos ativos, cada um com `slug`, `name`
e `enabled: true`. Cada membro contém ID, nome, e-mail e papel no Projeto.

```sh
curl --fail-with-body \
  --header "Authorization: Bearer ${API_KEY}" \
  --header "Accept: application/json" \
  "https://projetos.example/api/projects/portal-de-servicos"
```

Resposta resumida:

```json
{
  "data": {
    "id": 12,
    "slug": "portal-de-servicos",
    "name": "Portal de serviços",
    "description": "## Contexto\n\nDescrição integral.",
    "status": {"value": "ACTIVE", "label": "Ativo"},
    "type": {"id": 2, "slug": "desenvolvimento", "name": "Desenvolvimento"},
    "phase": {"id": 4, "slug": "production", "name": "Produção"},
    "parent": null,
    "tags": [{"id": 9, "name": "Integração", "slug": "integracao"}],
    "modules_enabled": [
      {"slug": "tasks", "name": "Tarefas", "enabled": true},
      {"slug": "meetings", "name": "Reuniões", "enabled": true}
    ],
    "web_url": "https://projetos.example/projects/portal-de-servicos",
    "created_at": "2026-08-30T09:00:00.000000Z",
    "updated_at": "2026-09-10T15:30:00.000000Z"
  }
}
```

## Reuniões

A coleção inclui Reuniões não excluídas e diretamente vinculadas ao Projeto,
inclusive as concluídas. Um vínculo apenas com o Projeto pai não é herdado por
um subprojeto.

| Filtro | Regra |
| --- | --- |
| `status[]` | um ou mais valores `DRAFT`, `SCHEDULED`, `ONGOING` ou `COMPLETED` |
| `scheduled_from` | limite inferior inclusivo em ISO 8601 com fuso |
| `scheduled_to` | limite superior inclusivo em ISO 8601 com fuso |
| `search` | título ou local, sem diferenciar maiúsculas e minúsculas |
| `page` | página, inteiro a partir de 1 |
| `per_page` | inteiro de 1 a 100; padrão 20 |

A ordem fixa é `scheduled_at desc`, `id desc`.

```sh
curl --get --fail-with-body \
  --header "Authorization: Bearer ${API_KEY}" \
  --header "Accept: application/json" \
  --data-urlencode "status[]=COMPLETED" \
  --data-urlencode "scheduled_from=2026-09-01T00:00:00Z" \
  --data-urlencode "search=planejamento" \
  --data-urlencode "per_page=20" \
  "https://projetos.example/api/projects/portal-de-servicos/meetings"
```

Cada item da coleção contém `id`, `title`, `status`, `scheduled_at`,
`location`, resumos dos `projects` vinculados, timestamps e `web_url`. A
coleção não inclui os campos textuais longos.

O detalhe acrescenta `notes` (Anotações prévias), `ata`, `transcription`, a
`agenda` ordenada, `comments`, `files`, `links` e `incoming_mentions`. Cada
Item de pauta informa `id`, `position`, `type`, `title`, `notes` e um resumo
opcional em `reference`. Cada comentário ativo informa ID, texto, timestamps
e autor por ID, nome e e-mail. Uma Reunião diretamente vinculada é retornada
integralmente mesmo que sua Pauta tenha referências externas.

```sh
curl --fail-with-body \
  --header "Authorization: Bearer ${API_KEY}" \
  --header "Accept: application/json" \
  "https://projetos.example/api/projects/portal-de-servicos/meetings/31"
```

Quando o módulo estiver desligado, coleção e detalhe respondem `409 Conflict`
com o código `meetings_module_disabled`.

## Tarefas

A coleção inclui Tarefas não excluídas do Projeto, inclusive concluídas.

| Filtro | Regra |
| --- | --- |
| `status[]` | um ou mais valores `NEW`, `ASSIGNED`, `IN_PROGRESS`, `IN_REVIEW`, `HOLD` ou `DONE` |
| `priority[]` | uma ou mais prioridades: `1` Urgente, `2` Alta, `3` Média ou `4` Baixa |
| `due_from` | vencimento mínimo inclusivo em `YYYY-MM-DD` |
| `due_to` | vencimento máximo inclusivo em `YYYY-MM-DD` |
| `tag[]` | um ou mais slugs; corresponde a qualquer uma das tags |
| `search` | título ou descrição, sem diferenciar maiúsculas e minúsculas |
| `page` | página, inteiro a partir de 1 |
| `per_page` | inteiro de 1 a 100; padrão 20 |

A ordem fixa é `updated_at desc`, `id desc`.

```sh
curl --get --fail-with-body \
  --header "Authorization: Bearer ${API_KEY}" \
  --header "Accept: application/json" \
  --data-urlencode "status[]=IN_PROGRESS" \
  --data-urlencode "priority[]=2" \
  --data-urlencode "tag[]=integracao" \
  --data-urlencode "due_to=2026-09-30" \
  --data-urlencode "per_page=20" \
  "https://projetos.example/api/projects/portal-de-servicos/tasks"
```

A lista retorna `id`, `title`, `description`, `status` e `priority` com valor e
rótulo, `start_date`, `due_date`, `completed_at`, timestamps, responsáveis em
`assignees` somente pelo nome, `tags` e `web_url`.

O detalhe acrescenta `project`, responsáveis com ID, nome, e-mail e papel no
Projeto, além de `comments`, `files`, `links` e `incoming_mentions`.

```sh
curl --fail-with-body \
  --header "Authorization: Bearer ${API_KEY}" \
  --header "Accept: application/json" \
  "https://projetos.example/api/projects/portal-de-servicos/tasks/81"
```

Quando o módulo estiver desligado, coleção e detalhe respondem `409 Conflict`
com o código `tasks_module_disabled`.

## Conteúdo incorporado nos detalhes

Projeto, Tarefa e Reunião usam a mesma estrutura para conteúdo relacionado:

- `comments` contém somente comentários ativos em ordem cronológica, com ID,
  texto, timestamps e autor por ID, nome e e-mail;
- `files.owned` contém Arquivos pertencentes à entidade e `files.shared`
  contém Arquivos compartilhados com ela;
- `links.owned` e `links.shared` aplicam a mesma separação aos Links;
- `incoming_mentions` informa `locations_count`, `sources_count` e `sources`,
  agrupando os locais pela entidade de origem.

Projeto e Tarefa retornam os grupos `shared` vazios, pois o compartilhamento
atual tem Reuniões como destino. Entradas próprias e compartilhadas são
mutuamente exclusivas. Os Arquivos incorporados usam a mesma representação da
coleção `/files`; os bytes continuam disponíveis exclusivamente no
`download_url`.

Somente Menções cuja origem também esteja no escopo do Projeto proprietário
da Chave são devolvidas. Uma Menção recebida não concede acesso a outro
Projeto, Tarefa ou Reunião.

## Arquivos

A coleção reúne, sem duplicatas, Arquivos cujo Proprietário do arquivo seja o
próprio Projeto, uma Tarefa do Projeto ou uma Reunião diretamente vinculada.
Também inclui Arquivos explicitamente compartilhados com essas Reuniões. Um
caminho por recurso excluído ou módulo desligado não concede acesso; um
caminho alternativo ativo mantém o Arquivo disponível.

| Filtro | Regra |
| --- | --- |
| `search` | nome exibido do Arquivo |
| `owner_type[]` | um ou mais valores `project`, `task` ou `meeting` |
| `mime_type[]` | um ou mais tipos MIME por correspondência exata |
| `uploaded_from` | limite inferior inclusivo em ISO 8601 com fuso |
| `uploaded_to` | limite superior inclusivo em ISO 8601 com fuso |
| `page` | página, inteiro a partir de 1 |
| `per_page` | inteiro de 1 a 100; padrão 20 |

A ordem fixa é `uploaded_at desc`, `id desc`.

```sh
curl --get --fail-with-body \
  --header "Authorization: Bearer ${API_KEY}" \
  --header "Accept: application/json" \
  --data-urlencode "owner_type[]=project" \
  --data-urlencode "mime_type[]=application/pdf" \
  --data-urlencode "uploaded_from=2026-09-01T00:00:00Z" \
  --data-urlencode "per_page=20" \
  "https://projetos.example/api/projects/portal-de-servicos/files"
```

Cada item contém `uuid`, nome exibido em `name`, `extension`, `mime_type`,
`size`, `uploaded_at`, resumo adaptado do `owner` e `download_url`. Nome
original, Autor do arquivo, disco, caminho e miniatura não são expostos.

O detalhe entrega os bytes originais, inclusive para imagens e PDFs. Use o
mesmo Bearer e preserve o nome sugerido pelo servidor:

```sh
curl --fail-with-body --remote-header-name --remote-name \
  --header "Authorization: Bearer ${API_KEY}" \
  "https://projetos.example/api/projects/portal-de-servicos/files/550e8400-e29b-41d4-a716-446655440000"
```

A resposta inclui tipo e tamanho, além de
`Content-Disposition: attachment` e `X-Content-Type-Options: nosniff`. O UUID
de um Arquivo sem caminho ativo no Projeto responde `404`.

## Paginação e validação

As coleções usam `page` e `per_page` e mantêm o envelope do paginador Laravel
com `data`, `links` e `meta`. `meta` informa, entre outros valores,
`current_page`, `last_page`, `per_page` e `total`. Os filtros permanecem nas
URLs de navegação.

```json
{
  "data": [],
  "links": {"first": "...page=1", "last": "...page=3", "prev": null, "next": "...page=2"},
  "meta": {"current_page": 1, "last_page": 3, "per_page": 20, "total": 45}
}
```

Arrays aceitam a notação `campo[]=valor`. Enumerações, datas, intervalos,
`page` ou `per_page` inválidos respondem `422 Unprocessable Content` no
formato de validação do Laravel, com `message` e `errors`. Não há ordenação
configurável nem opção de desabilitar a paginação.

## Erros e limite de chamadas

| Status | Significado |
| --- | --- |
| `401 Unauthorized` | Bearer ausente, inválido, expirado ou revogado |
| `403 Forbidden` | chave válida sem a ability exigida |
| `404 Not Found` | slug, ID ou UUID inexistente, excluído ou fora do escopo |
| `409 Conflict` | módulo necessário desligado; veja o campo `code` |
| `422 Unprocessable Content` | paginação, filtro, enumeração ou intervalo inválido |
| `429 Too Many Requests` | limite de chamadas excedido |

O grupo `/api` aceita 60 requisições por minuto por endereço IP. Listagens,
detalhes e downloads contam igualmente. Não há cota adicional por chave ou por
volume de bytes.

## Alteração do slug

Alterar o slug do Projeto quebra todas as URLs da API. A mudança não revoga a
chave, mas não há redirecionamento nem alias para o slug anterior. Antes da
alteração, identifique os consumidores; depois, é necessário atualizar o slug
configurado em todos os consumidores e validar novamente as sete rotas. A
interface avisa sobre essa quebra quando o Projeto possui uma chave ativa.

## Compatibilidade operacional

A aplicação exige PHP 8.3 ou versão compatível com `^8.3` e usa
`uspdev/api-keys:^0.1`. Para conferir o contrato publicado em uma revisão:

```sh
php artisan route:list --path=api
php artisan test tests/Feature/ApiReadOnlyContractTest.php
php artisan test tests/Feature/ProjectApiTest.php tests/Feature/MeetingApiTest.php tests/Feature/TaskApiTest.php tests/Feature/FileApiTest.php
```

O roteiro de consumo ponta a ponta está em
[Teste manual da API de leitura](teste-manual-leitura-por-projeto.md). Para usar
uma coleção gráfica, consulte o
[teste manual no Postman](teste-manual-leitura-por-projeto-postman.md).
