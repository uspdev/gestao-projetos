# Dados expostos pela API

**Status:** aceito

A API fornecerá representações explícitas e mínimas de Projetos, Tarefas e
Solicitações. Os modelos não serão serializados diretamente, para que campos
internos não passem a integrar o contrato externo por acidente.

## Contexto

Sistemas clientes e modelos LLM precisam de contexto suficiente para conhecer
um Projeto, ler suas Tarefas e acompanhar Solicitações. Os modelos da aplicação
também possuem informações de autorização, auditoria e relacionamento humano
que não são necessárias para esses usos e não devem ser expostas apenas por
existirem no banco de dados.

## Decisão

- A leitura de Projeto retornará:
  - `id`, `slug`, `name` e `description`;
  - estado com valor técnico e rótulo;
  - tipo e fase do Projeto;
  - resumo do Projeto pai, quando existir;
  - tags;
  - identificadores dos módulos habilitados, incluindo o estado do módulo de
    Tarefas;
  - `web_url`, `created_at` e `updated_at`.
- A descrição do Projeto será devolvida em seu Markdown original, sem renderizar
  HTML.
- A leitura do Projeto não exporá membros, papéis de usuários, visibilidade,
  regras de herança de permissões ou campos de autoria e auditoria.
- Toda representação de Tarefa, tanto na listagem quanto na consulta
  individual, retornará:
  - `id`, `title` e a `description` integral em seu Markdown original;
  - estado e prioridade com seus valores técnicos e rótulos;
  - datas de início, vencimento, conclusão, criação e atualização;
  - responsáveis identificados apenas pelo nome, sem e-mail, número USP ou
    outros atributos pessoais;
  - tags e `web_url`.
- Não haverá uma representação resumida de Tarefa sem descrição. Comentários,
  arquivos, links, Menções, histórico de auditoria e o vínculo de origem com
  uma Solicitação não integrarão essas respostas.
- Toda representação de Solicitação, tanto na listagem quanto na consulta
  individual, retornará:
  - `id`, `title`, a `description` integral em texto simples e `source_url`;
  - estado com valor técnico e rótulo;
  - `response`, nula enquanto a Solicitação estiver pendente;
  - `created_at`, `updated_at` e `evaluated_at`;
  - quando aceita, um resumo da Tarefa criada com `id`, `title` e `web_url`;
  - `web_url` da Solicitação para acesso à interface interna.
- A resposta de criação usará a mesma representação integral, acompanhada do
  cabeçalho `Location` para a URL canônica de consulta da Solicitação na API.
- O usuário avaliador será persistido para autorização e auditoria internas,
  mas sua identidade não será exposta ao Sistema cliente.
- As respostas serão construídas por recursos de API dedicados. A serialização
  direta e irrestrita dos modelos não fará parte do contrato.

## Consequências

O consumidor recebe o contexto geral necessário sem conhecer a estrutura
interna completa do Projeto. Novos campos adicionados aos modelos não serão
automaticamente expostos, e qualquer ampliação do contrato exigirá uma decisão
deliberada.
