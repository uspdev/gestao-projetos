# Teste manual da API de leitura por Projeto

Este roteiro exercita a emissão e a revogação de uma Chave de API, a leitura
do Projeto, as três listagens, os detalhes de Reunião e Tarefa, o download de
Arquivo, filtros, paginação e respostas de erro representativas.

Use dados de teste que contenham ao menos uma Reunião, uma Tarefa e um Arquivo.
Para validar conteúdo integral, prefira uma Reunião com Anotações prévias, Ata,
Transcrição, Pauta e comentário, e uma Tarefa com descrição, prioridade, prazo,
responsável e tag.

Para executar o mesmo fluxo no Postman, consulte o
[teste manual da API de leitura no Postman](teste-manual-leitura-por-projeto-postman.md).

## 1. Emitir a credencial

1. entre na interface como administrador diretamente vinculado ao Projeto;
2. abra as [configurações do Projeto no servidor de desenvolvimento](https://dev13.sti.eesc.usp.br/eduardoferraz/gestao-projetos/public/projects/gestao-projetos/settings#project-api-keys-settings)
   e localize **Chaves de API**;
3. escolha finalidade **Integração** ou **IA** e papel **Visualizador** ou
   **Contribuidor**;
4. use o gerenciador para emitir uma Chave de API;
5. copie a credencial completa, exibida somente uma vez.

Os dois papéis devem permitir as mesmas leituras neste contrato. Em outro
terminal, carregue a chave sem imprimi-la e informe a origem e o slug:

```sh
read -rsp 'Cole a chave e pressione Enter: ' API_TEST_KEY
printf '\n'
API_BASE_URL='https://dev13.sti.eesc.usp.br/eduardoferraz/gestao-projetos/public'
PROJECT_SLUG='gestao-projetos'
```

A variável `API_BASE_URL` termina na raiz pública da aplicação. Não inclua o
caminho `/projects/gestao-projetos/settings` nem o fragmento
`#project-api-keys-settings` da página usada para emitir a chave.

## 2. Ler o Projeto

```sh
curl -i \
  --header "Authorization: Bearer ${API_TEST_KEY}" \
  --header "Accept: application/json" \
  "${API_BASE_URL}/api/projects/${PROJECT_SLUG}"
```

Confirme `200 OK`, `data.slug`, `data.description`, `data.visibility`,
`data.permission_inheritance`, `data.modules.enabled`, `data.modules.items`,
`data.members`, `data.comments`, `data.files`, `data.links`,
`data.incoming_mentions`, `data.agenda_meetings` e `data.subprojects`.

## 3. Listar e detalhar Reuniões

```sh
curl --get -i \
  --header "Authorization: Bearer ${API_TEST_KEY}" \
  --header "Accept: application/json" \
  --data-urlencode "search=planejamento" \
  --data-urlencode "per_page=5" \
  "${API_BASE_URL}/api/projects/${PROJECT_SLUG}/meetings"
```

Confirme o envelope `data`, `links` e `meta`, a paginação solicitada e a
ausência de Anotações prévias, Ata e Transcrição na coleção. Copie um ID:

```sh
MEETING_ID='31'
curl -i \
  --header "Authorization: Bearer ${API_TEST_KEY}" \
  --header "Accept: application/json" \
  "${API_BASE_URL}/api/projects/${PROJECT_SLUG}/meetings/${MEETING_ID}"
```

Confirme o conteúdo integral em `notes`, `ata`, `transcription`, `agenda`,
`comments`, `files`, `links` e `incoming_mentions`. Cada Item de pauta deve
ter `id` e `position`; comentários devem identificar comentário e autor.

## 4. Listar e detalhar Tarefas

Escolha um estado existente nos dados de teste:

```sh
curl --get -i \
  --header "Authorization: Bearer ${API_TEST_KEY}" \
  --header "Accept: application/json" \
  --data-urlencode "status[]=IN_PROGRESS" \
  --data-urlencode "per_page=5" \
  "${API_BASE_URL}/api/projects/${PROJECT_SLUG}/tasks"
```

Confirme o envelope paginado, a ordem decrescente e a descrição integral dos
itens. Copie um ID:

```sh
TASK_ID='81'
curl -i \
  --header "Authorization: Bearer ${API_TEST_KEY}" \
  --header "Accept: application/json" \
  "${API_BASE_URL}/api/projects/${PROJECT_SLUG}/tasks/${TASK_ID}"
```

Confirme descrição, estado, prioridade, datas, tags, URL web, Projeto,
responsáveis identificados por ID e papel, `comments`, `files`, `links` e
`incoming_mentions`. E-mails não devem aparecer.

## 5. Listar e baixar Arquivos

```sh
curl --get -i \
  --header "Authorization: Bearer ${API_TEST_KEY}" \
  --header "Accept: application/json" \
  --data-urlencode "owner_type[]=project" \
  --data-urlencode "per_page=5" \
  "${API_BASE_URL}/api/projects/${PROJECT_SLUG}/files"
```

Confirme o envelope paginado, uma única entrada por UUID, o resumo do
Proprietário do arquivo e `download_url`. Copie um UUID e baixe para um arquivo
temporário:

```sh
FILE_UUID='550e8400-e29b-41d4-a716-446655440000'
curl \
  --header "Authorization: Bearer ${API_TEST_KEY}" \
  --dump-header /tmp/api-project-file.headers \
  --output /tmp/api-project-file \
  "${API_BASE_URL}/api/projects/${PROJECT_SLUG}/files/${FILE_UUID}"
```

Confirme os bytes, `Content-Type`, `Content-Length`,
`Content-Disposition: attachment` e `X-Content-Type-Options: nosniff`. Repita
sem o cabeçalho Bearer e confirme `401 Unauthorized`.

## 6. Exercitar erros e isolamento

Filtro inválido:

```sh
curl -i \
  --header "Authorization: Bearer ${API_TEST_KEY}" \
  --header "Accept: application/json" \
  "${API_BASE_URL}/api/projects/${PROJECT_SLUG}/tasks?per_page=101"
```

Confirme `422 Unprocessable Content` e a chave `errors.per_page`.

Para validar o isolamento entre Projetos, use o slug de outro Projeto com a
mesma chave e confirme `404 Not Found`. Faça o mesmo com um ID ou UUID que
exista somente no outro Projeto.

Para validar módulos desligados em dados de teste, desative temporariamente o
módulo de Tarefas ou Reuniões pela interface e confirme `409 Conflict` com
`tasks_module_disabled` ou `meetings_module_disabled`. Reative o módulo após o
teste. Arquivos pertencentes diretamente ao Projeto devem continuar legíveis.

## 7. Revogar e encerrar

Volte ao gerenciador em **Chaves de API** e use a ação para revogar a Chave de
API. Repita a leitura do Projeto e confirme `401 Unauthorized`. Uma chave
revogada não deve voltar a funcionar.

Remova o Arquivo e os cabeçalhos temporários conforme a política do ambiente.
O roteiro não valida DNS, TLS, proxies ou limites de infraestrutura de um
ambiente implantado; esses pontos exigem uma verificação separada nesse
ambiente.
