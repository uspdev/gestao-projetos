# Rotas de API sem versionamento inicial

**Status:** aceito

> **Revisão posterior:** o ADR 0010 substitui a criação direta de Tarefas por
> um fluxo em que a API cria uma Solicitação que poderá originar uma Tarefa.
> O contrato de Solicitações passa a usar as rotas de criação, listagem e
> consulta individual registradas abaixo.

As rotas de negócio desta integração usarão o prefixo `/api` sem um segmento de versão inicial, seguindo os exemplos e o contrato de integração documentados pelo package `uspdev/api-keys`. O versionamento explícito poderá ser introduzido posteriormente quando houver uma mudança incompatível que justifique preservar uma versão anterior.

## Contexto

O projeto ainda não possui consumidores nem uma convenção de versionamento para sua API; `routes/api.php` contém somente o esqueleto padrão do Laravel. O package apresenta `/api/projects/{project}/tasks` como exemplo de rota da aplicação hospedeira.

## Decisão

- A leitura de Projeto será exposta em `/api/projects/{project}`.
- A listagem paginada de Tarefas será exposta em
  `/api/projects/{project}/tasks`.
- A leitura detalhada de uma Tarefa será exposta em
  `/api/projects/{project}/tasks/{task}`.
- O parâmetro `{task}` será o identificador numérico já usado pelas Tarefas.
- A Tarefa consultada deverá pertencer ao Projeto indicado na URL; caso
  contrário, a API responderá como recurso não encontrado.
- A listagem paginada das Solicitações do Sistema cliente autenticado será
  exposta em `/api/projects/{project}/requests`.
- Ambas as listagens usarão a paginação convencional do Laravel, com 20 itens
  por padrão e resposta contendo `data`, `links` e `meta`.
- O parâmetro opcional `per_page` aceitará valores entre 1 e 100. As rotas não
  oferecerão uma opção para retornar toda a coleção sem paginação.
- A listagem de Tarefas incluirá por padrão todas as Tarefas não excluídas,
  inclusive as concluídas, ordenadas por `updated_at` decrescente e, em caso
  de empate, por identificador decrescente.
- A listagem de Tarefas aceitará filtro opcional por um ou mais valores já
  definidos em `TaskStatus`: `NEW`, `ASSIGNED`, `IN_PROGRESS`, `IN_REVIEW`,
  `HOLD` e `DONE`.
- A listagem de Solicitações incluirá por padrão todos os estados, ordenados
  por `created_at` decrescente e, em caso de empate, por identificador
  decrescente.
- A listagem de Solicitações aceitará filtro opcional pelos estados
  `pending`, `accepted` e `rejected`.
- A criação de Solicitações usará
  `POST /api/projects/{project}/requests`.
- A criação bem-sucedida responderá `201 Created`, retornará a representação
  integral da Solicitação e incluirá `Location` com sua URL canônica de
  consulta na API.
- A leitura detalhada de uma Solicitação usará
  `GET /api/projects/{project}/requests/{request}`.
- O parâmetro `{request}` será o identificador numérico da Solicitação.
- A Solicitação consultada deverá pertencer ao Sistema cliente autenticado e
  ao Projeto indicado na URL; qualquer divergência será respondida como
  recurso não encontrado, sem confirmar a existência de recursos fora desse
  escopo.
- A criação direta de Tarefas inicialmente prevista para
  `POST /api/projects/{project}/tasks` foi substituída pelo fluxo definido no
  ADR 0010.
- Sistemas clientes não terão rotas para alterar, excluir, aceitar ou rejeitar
  Solicitações.
- O parâmetro `{project}` continuará sendo o `slug` do Projeto.
- Quando o módulo de Tarefas estiver desabilitado, a leitura do Projeto
  continuará disponível e indicará esse estado. A listagem e a consulta
  individual de Tarefas responderão `409 Conflict` com o código
  `tasks_module_disabled`.
- As respostas `401` e `403` do middleware do package serão preservadas com o
  campo `message`, sem uma camada de adaptação da aplicação.
- Recursos não encontrados responderão `404` com `message`. Conflitos de
  estado responderão `409` com `message` e um `code` técnico estável.
- Erros de validação `422` e excesso de requisições `429` manterão os formatos
  convencionais produzidos pelo Laravel e pelo limitador existente.
- A primeira versão não introduzirá um envelope global próprio para erros.
- Uma alteração incompatível futura deverá introduzir uma versão compatível ou migrar os consumidores de forma explícita.

## Opções consideradas

- **Adicionar `/v1` desde o início:** rejeitada por não haver uma API anterior, uma convenção existente ou necessidade imediata de manter contratos paralelos.
- **Manter `/api` e alterar silenciosamente o contrato:** rejeitada porque quebraria integrações de LLM sem uma fronteira de migração clara.

## Consequências

As URLs iniciais são menores e permanecem alinhadas à documentação do package. Caso uma evolução incompatível exija versionamento, a aplicação deverá introduzir uma nova superfície de rota e comunicar a migração aos consumidores.
