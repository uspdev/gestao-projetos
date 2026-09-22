# Teste manual da API de leitura por Projeto no Postman

Este roteiro reproduz no Postman o teste manual da API de leitura por Projeto.
Ele cobre emissão e revogação da Chave de API, as sete rotas `GET`, filtros,
paginação, isolamento, módulos desligados e ausência de métodos de escrita.

Use um Projeto de teste que contenha ao menos uma Reunião, uma Tarefa e um
Arquivo. Para conferir o conteúdo integral, escolha uma Reunião com Anotações
prévias, Ata, Transcrição, Pauta e comentário, e uma Tarefa com descrição,
prioridade, prazo, responsável e tag.

## 1. Emitir a Chave de API

1. entre na aplicação como administrador diretamente vinculado ao Projeto;
2. abra as [configurações do Projeto no servidor de desenvolvimento](https://dev13.sti.eesc.usp.br/eduardoferraz/gestao-projetos/public/projects/gestao-projetos/settings#project-api-keys-settings)
   e localize **Chaves de API**;
3. escolha finalidade **Integração** ou **IA** e papel **Visualizador** ou
   **Contribuidor**;
4. use o gerenciador para emitir a chave;
5. copie a credencial completa, exibida somente uma vez.

Os dois papéis devem permitir as mesmas leituras neste contrato.

## 2. Criar o ambiente

No Postman, crie um ambiente chamado `Gestão de Projetos` com as variáveis
abaixo. Marque `api_key` como informação sensível e informe a chave no valor
local, sem sincronizá-la ou exportá-la.

| Variável | Valor de exemplo |
| --- | --- |
| `base_url` | `https://dev13.sti.eesc.usp.br/eduardoferraz/gestao-projetos/public` |
| `project_slug` | `gestao-projetos` |
| `api_key` | chave completa emitida |
| `meeting_id` | vazio; copie um ID retornado pela listagem |
| `task_id` | vazio; copie um ID retornado pela listagem |
| `file_uuid` | vazio; copie um UUID retornado pela listagem |
| `other_project_slug` | slug de outro Projeto usado para validar isolamento |

Selecione o ambiente antes de enviar as requisições. A variável `base_url`
corresponde à raiz pública da aplicação no servidor de desenvolvimento; ela
não inclui `/projects/gestao-projetos/settings` nem o fragmento
`#project-api-keys-settings` da página usada para emitir a chave.

## 3. Criar a coleção

Crie uma coleção chamada `Gestão de Projetos - API de leitura` e configure:

- em **Authorization**, tipo **Bearer Token** e token `{{api_key}}`;
- em **Headers**, `Accept: application/json`;
- em cada requisição, **Inherit auth from parent**.

Não coloque a chave na URL nem em parâmetros. O fallback `api_key` está
desabilitado.

## 4. Ler o Projeto

Crie e envie `GET Projeto`:

```text
GET {{base_url}}/api/projects/{{project_slug}}
```

Confirme visualmente:

- `200 OK`;
- `data.slug` igual a `project_slug`;
- presença de `description` e `modules.enabled`;
- ausência de membros e coleções incorporadas de Tarefas, Reuniões ou
  Arquivos.

## 5. Listar e detalhar Reuniões

Crie `GET Reuniões`:

```text
GET {{base_url}}/api/projects/{{project_slug}}/meetings
```

Na aba **Params**, habilite:

| Chave | Valor |
| --- | --- |
| `search` | `planejamento` ou outro texto existente |
| `per_page` | `5` |

O Postman codifica os parâmetros. Para testar múltiplos estados, repita a
chave `status[]`, por exemplo com `SCHEDULED` e `COMPLETED`.

Confirme `200 OK`, o envelope `data`, `links` e `meta`, e
`meta.per_page` igual a `5`. Os itens da coleção não devem conter `notes`,
`ata` ou `transcription`.

Desabilite temporariamente os filtros se eles não encontrarem registros.
Copie o `id` de uma Reunião retornada para `meeting_id` no ambiente e crie
`GET Reunião`:

```text
GET {{base_url}}/api/projects/{{project_slug}}/meetings/{{meeting_id}}
```

Confirme `200 OK` e o conteúdo integral em `notes`, `ata`, `transcription`,
`agenda` e `comments`.

## 6. Listar e detalhar Tarefas

Crie `GET Tarefas`:

```text
GET {{base_url}}/api/projects/{{project_slug}}/tasks
```

Na aba **Params**, habilite:

| Chave | Valor |
| --- | --- |
| `status[]` | `IN_PROGRESS` ou outro estado existente |
| `per_page` | `5` |

Outros filtros podem ser combinados repetindo `priority[]` e `tag[]` ou
informando `due_from`, `due_to` e `search`.

Confirme `200 OK`, o envelope `data`, `links` e `meta`,
`meta.per_page` igual a `5` e a presença de descrição, estado, prioridade,
responsáveis, tags e URL web nos itens.

Desabilite temporariamente os filtros se necessário. Copie o `id` de uma
Tarefa retornada para `task_id` no ambiente e crie `GET Tarefa`:

```text
GET {{base_url}}/api/projects/{{project_slug}}/tasks/{{task_id}}
```

Confirme `200 OK`, o mesmo identificador e a representação integral da Tarefa,
incluindo descrição, estado, prioridade, datas, responsáveis, tags e URL web.

## 7. Listar e baixar Arquivos

Crie `GET Arquivos`:

```text
GET {{base_url}}/api/projects/{{project_slug}}/files
```

Na aba **Params**, habilite:

| Chave | Valor |
| --- | --- |
| `owner_type[]` | `project` |
| `per_page` | `5` |

Também é possível combinar `search`, chaves repetidas `mime_type[]` e os
limites `uploaded_from` e `uploaded_to` em ISO 8601.

Confirme `200 OK`, o envelope paginado e uma única entrada por UUID. Cada item
deve conter `uuid`, `name`, `extension`, `mime_type`, `size`, `uploaded_at`,
`owner` e `download_url`.

Desabilite o filtro se necessário. Copie o `uuid` de um Arquivo retornado para
`file_uuid` no ambiente e crie `GET Download do Arquivo`:

```text
GET {{base_url}}/api/projects/{{project_slug}}/files/{{file_uuid}}
```

Confirme `200 OK`, `Content-Type`, `Content-Length`,
`Content-Disposition: attachment` e `X-Content-Type-Options: nosniff`. Use
**Send and Download** para salvar os bytes. O nome original, o caminho e o
disco não devem aparecer na resposta.

## 8. Exercitar erros

Crie uma pasta `Erros` na coleção e duplique as requisições indicadas abaixo.

### Chave ausente

Duplique `GET Projeto`, altere **Authorization** para **No Auth** e confirme
`401 Unauthorized`.

### Filtro inválido

Duplique `GET Tarefas`, deixe apenas `per_page=101` e confirme
`422 Unprocessable Content` com a chave `errors.per_page`.

### Isolamento entre Projetos

Crie a requisição abaixo usando a mesma chave:

```text
GET {{base_url}}/api/projects/{{other_project_slug}}
```

Ela deve responder `404 Not Found`. Repita com um ID ou UUID pertencente
somente ao outro Projeto.

### Módulo desligado

Desative temporariamente Tarefas ou Reuniões pela interface e execute a
listagem correspondente. Confirme `409 Conflict` e `tasks_module_disabled` ou
`meetings_module_disabled`. Reative o módulo depois do teste. Arquivos
pertencentes diretamente ao Projeto devem continuar disponíveis.

### Métodos de escrita ausentes

Duplique cada uma das sete requisições e altere o método para `POST`, `PUT`,
`PATCH` ou `DELETE`, sem enviar corpo. Cada combinação deve responder
`405 Method Not Allowed`.

## 9. Revogar e encerrar

Volte ao gerenciador em **Chaves de API**, revogue a chave e execute novamente
`GET Projeto`. Confirme `401 Unauthorized`; uma chave revogada não deve voltar
a funcionar.

Apague o valor local de `api_key` ao concluir. Não exporte a chave junto com o
ambiente ou a coleção. Este roteiro não valida DNS, TLS, proxies ou limites de
infraestrutura de um ambiente implantado.

## Referências do Postman

- [Ambientes e variáveis](https://learning.postman.com/docs/use/send-requests/variables/environment-variables)
- [Autorização herdada](https://learning.postman.com/docs/use/send-requests/authorization/specifying-authorization-details)
