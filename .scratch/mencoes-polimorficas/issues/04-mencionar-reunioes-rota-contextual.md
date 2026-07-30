# 04 — Mencionar Reuniões com rota contextual

**O que construir:** permitir que Reuniões sejam encontradas e mencionadas por título, resolvendo sua navegação de forma diferente para cada leitor. A Menção guarda somente a Reunião, enquanto o link escolhe dinamicamente um Projeto vinculado e autorizado.

**Bloqueado por:** 02 — Mencionar Projetos com busca e navegação autorizadas; 03 — Mencionar Tarefas com prioridade contextual.

**Status:** ready-for-agent

- [ ] Registrar Reunião como Entidade mencionável sob o alias `meeting`.
- [ ] Inserir Menção a Reunião com ID numérico imutável e Rótulo histórico baseado no título.
- [ ] Incluir Reuniões no autocomplete unificado, com grupo e filtro próprios.
- [ ] Em Projeto, Tarefa, Reunião, Item de pauta e Comentário, priorizar Reuniões pertencentes ao contexto antes de outras Reuniões visíveis.
- [ ] Usar os Projetos vinculados, Projetos da pauta e Tarefas da pauta para compor a prioridade contextual já definida.
- [ ] Pesquisar fora do contexto somente Reuniões que o autor possa visualizar por pelo menos um Projeto válido.
- [ ] Omitir e rejeitar Menção de uma Reunião para ela própria em suas Anotações prévias.
- [ ] Persistir somente a identidade da Reunião, sem gravar o Projeto usado pela rota.
- [ ] Na apresentação, escolher dinamicamente um Projeto vinculado que o leitor possa visualizar e cujo módulo de Reuniões esteja habilitado.
- [ ] Renderizar Reunião disponível como `@{título atual}` e construir o link com o Projeto contextual escolhido.
- [ ] Renderizar falta de permissão quando a Reunião existir, mas nenhum Projeto vinculado produzir uma rota autorizada.
- [ ] Renderizar destino não encontrado quando a Reunião estiver excluída ou inexistente.
- [ ] Preservar relações durante exclusão lógica e recuperá-las na restauração; limpar entradas na exclusão definitiva.
- [ ] Comprovar que mudanças posteriores nos Projetos vinculados alteram a resolução da rota sem invalidar ou reescrever a Menção.
- [ ] Cobrir Reunião multiprojeto, leitores com conjuntos diferentes de Projetos, módulo desabilitado, autorreferência, renomeação e ciclo de vida pelos pontos web e do módulo.
