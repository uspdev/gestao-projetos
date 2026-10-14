# 06 — Retirar Solicitações

**O que construir:** retirar do Gestão de Projetos a entrada e a avaliação de Solicitações, preservando a criação manual de Tarefas na interface existente.

**Blocked by:** Nenhum — pode iniciar imediatamente.

**Status:** ready-for-agent

- [ ] Não existem mais rotas de negócio para criar, listar ou consultar Solicitações, nem páginas ou ações web de fila, aceitação ou rejeição; a API de negócio não expõe escrita.
- [ ] Menus, contadores, policies, validações, modelos, serviços, relações, persistência e testes específicos de Solicitações deixam de integrar a aplicação, sem manter estrutura legada ou migração de dados não implantados.
- [ ] A criação manual de Tarefa a partir da interface existente continua funcional, sem dependência de uma Solicitação; seus testes de comportamento permanecem verdes.
- [ ] O restante da API e a administração de Chaves de API continuam operacionais durante a retirada; referências temporárias a Sistemas clientes só serão eliminadas no ticket 07.
- [ ] Testes HTTP verificam a ausência das operações de Solicitação e o fluxo manual de Tarefas, sem preservar testes de um comportamento descartado.
