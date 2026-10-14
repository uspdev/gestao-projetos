# 04 — Receber e acompanhar Solicitações

**O que construir:** permitir que um Sistema cliente com permissão de contribuição envie propostas de correção ou melhoria, acompanhe somente o próprio histórico e torne essas propostas visíveis para a triagem interna do Projeto.

**Blocked by:** 02 — Expor o contexto do Projeto pela API.

**Status:** ready-for-agent

- [ ] Criar a persistência de Solicitação com vínculos obrigatórios ao Projeto e ao Sistema cliente, estados `pending`, `accepted` e `rejected`, conteúdo original, avaliação e Tarefa resultante única.
- [ ] Preservar Solicitações como histórico sem exclusão lógica ou ação de exclusão e permitir que a referência ao avaliador fique nula se o usuário for removido.
- [ ] Expor criação, listagem e detalhe de Solicitações usando slug do Projeto e ID numérico da Solicitação.
- [ ] Exigir `requests.create` para criação e `requests.read` para consultas, garantindo que cada Sistema cliente veja apenas as próprias Solicitações no próprio Projeto.
- [ ] Aceitar somente título obrigatório de 3 a 120 caracteres, descrição obrigatória em texto simples de até 10.000 caracteres e URL de origem opcional absoluta `http` ou `https` de até 2.048 caracteres.
- [ ] Aparar os campos obrigatórios, rejeitar conteúdo vazio, não truncar valores e nunca requisitar a URL de origem no servidor.
- [ ] Rejeitar campos controlados pelo servidor, incluindo estado, resposta, avaliador e Tarefa; iniciar toda Solicitação como pendente.
- [ ] Responder à criação com `201 Created`, representação integral e cabeçalho `Location` para o detalhe canônico.
- [ ] Retornar lista e detalhe com conteúdo integral, estado e rótulo, resposta, datas, URLs e resumo da Tarefa quando houver, sem expor a identidade do avaliador.
- [ ] Paginar com 20 itens por padrão e `per_page` entre 1 e 100, ordenar pelas mais recentes e permitir filtro pelos estados acordados.
- [ ] Manter leitura de Solicitações quando o módulo de Tarefas estiver desabilitado, mas responder `409 tasks_module_disabled` a novas criações.
- [ ] Adicionar junto às Tarefas uma fila interna de Solicitações com contagem de pendentes à esquerda do título e páginas de listagem e consulta.
- [ ] Permitir a visualização interna somente a administradores e contribuidores com vínculo local, recusando visualizadores, acesso herdado e administrador global sem vínculo.
- [ ] Cobrir validações, abilities, isolamento, respostas, paginação, filtros, módulo e visibilidade interna por testes HTTP integrados.
