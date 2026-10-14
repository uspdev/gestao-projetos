# 02 — Leitura filtrada de Tarefas

**O que construir:** permitir que uma Chave do Projeto liste e consulte Tarefas com descrição integral, filtros e paginação, sem ultrapassar o escopo do Projeto.

**Blocked by:** 01 — Chaves do Projeto e leitura do Projeto.

**Status:** ready-for-agent

- [ ] `GET /api/projects/{project}/tasks` e `/tasks/{task}` aceitam a Chave do próprio Projeto com `tasks.read`; uma Tarefa de outro Projeto, excluída ou inexistente responde `404`.
- [ ] A lista retorna descrição integral, estado e prioridade, datas, tags, URL web e responsáveis pelo nome. O detalhe acrescenta Projeto, responsáveis com ID e papel, comentários, Arquivos e Links próprios e Menções recebidas, sem expor e-mail.
- [ ] A lista inclui Tarefas concluídas e aceita `status[]`, `priority[]`, `due_from`, `due_to`, `tag[]` por slug com correspondência a qualquer tag e `search` em título/descrição.
- [ ] Datas de vencimento usam intervalo inclusivo `YYYY-MM-DD`; valores ou intervalos inválidos respondem `422` no formato Laravel.
- [ ] A lista usa ordem fixa `updated_at desc`, `id desc` e paginação `page`/`per_page`, com 20 itens por padrão, 1–100 por página e envelope `data`/`links`/`meta`.
- [ ] Com o módulo de Tarefas desativado, lista e detalhe respondem `409` com `tasks_module_disabled`; o Projeto fora do escopo nunca é revelado por esse conflito.
- [ ] Testes HTTP exercitam ambos os papéis, filtros combinados, paginação, representação integral, módulo desativado e isolamento; não quebram o fluxo de desenvolvimento antigo antes de sua retirada.
