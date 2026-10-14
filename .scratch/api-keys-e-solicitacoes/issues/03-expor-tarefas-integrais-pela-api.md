# 03 — Expor Tarefas integrais pela API

**O que construir:** permitir que Sistemas clientes autorizados listem e consultem as Tarefas do seu Projeto sempre com a descrição integral e com paginação e filtros adequados para integrações e modelos LLM.

**Blocked by:** 02 — Expor o contexto do Projeto pela API.

**Status:** ready-for-agent

- [ ] Expor listagem e detalhe de Tarefas usando slug do Projeto e ID numérico da Tarefa, protegidos pela ability `tasks.read`.
- [ ] Restringir os resultados às Tarefas não excluídas do Projeto autenticado e responder `404` sem revelar Tarefas de outro Projeto.
- [ ] Incluir em toda representação, inclusive na listagem, ID, título, descrição Markdown integral, estado e prioridade com valor e rótulo, datas, responsáveis, tags e URL web.
- [ ] Expor responsáveis somente pelo nome, sem e-mail, número USP ou outros atributos pessoais.
- [ ] Não expor comentários, arquivos, links, Menções, histórico de auditoria nem vínculo de origem com Solicitação.
- [ ] Incluir por padrão Tarefas ativas e concluídas, ordenadas por atualização e ID decrescentes.
- [ ] Aceitar filtro opcional por um ou mais valores vigentes de `TaskStatus` e rejeitar valores inválidos com `422`.
- [ ] Paginar com 20 itens por padrão, permitir `per_page` de 1 a 100 e retornar `data`, `links` e `meta`, sem opção de coleção ilimitada.
- [ ] Responder `409 Conflict` com o código `tasks_module_disabled` na listagem e no detalhe quando o módulo estiver desabilitado.
- [ ] Cobrir conteúdo integral, paginação, ordenação, filtros, privacidade, escopo, soft delete e módulo desabilitado por testes HTTP integrados.
