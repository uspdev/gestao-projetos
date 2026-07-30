# 01 — Reestruturar Menções a usuário sobre o núcleo polimórfico

**O que construir:** preservar a experiência completa de Menção a usuário enquanto a infraestrutura passa a representar origem, campo e destino polimórficos por meio de um único módulo de Menções. Ao concluir, usuários continuam pesquisáveis, mencionáveis, renderizáveis e reconstruíveis, mas o sistema já possui o núcleo definitivo no qual os demais destinos serão adicionados.

**Bloqueado por:** Nenhum — pode começar imediatamente.

**Status:** ready-for-agent

- [ ] Reescrever diretamente a criação de `mentions` com origem polimórfica, `source_field` e destino polimórfico, sem estratégia de compatibilidade para a estrutura ainda não publicada.
- [ ] Persistir aliases estáveis em `source_type` e `target_type`, sem nomes completos de classes.
- [ ] Garantir unicidade por fonte, campo e destino e índices adequados para sincronização por fonte e consulta por destino.
- [ ] Remover `created_by`, timestamps e os nomes legados `mentionable_*`, `field` e `mentioned_user_id`.
- [ ] Expor `source()` e `target()` na Menção, `outgoingMentions()` nas fontes e `incomingMentions()` em Usuário.
- [ ] Concentrar pesquisa, sincronização, apresentação e reconstrução em um `MentionManager` concreto consumido pelos fluxos web e pelo comando operacional.
- [ ] Manter parsing e resolução por tipo como detalhes internos do módulo, com um adaptador funcional para Usuário e sem contrato público, repositório genérico ou API paralela.
- [ ] Reconhecer somente a sintaxe explícita de Menção e rejeitar tipo desconhecido, ID inválido ou outra construção malformada que se apresente como Menção.
- [ ] Continuar ignorando exemplos de sintaxe dentro de código inline e blocos de código.
- [ ] Manter links Markdown comuns fora do índice de Menções.
- [ ] Preservar as fontes atuais: descrição de Projeto, descrição de Tarefa, Anotações prévias de Reunião e Item de pauta e texto de Comentário ativo.
- [ ] Manter a elegibilidade atual de Menção a usuário baseada em participação direta no contexto, incluindo a possibilidade de o autor mencionar a si próprio.
- [ ] Validar todas as Menções na criação da fonte e somente destinos adicionados nas edições.
- [ ] Usar mensagem indistinguível para destino novo inexistente ou não permitido, sem revelar qual condição ocorreu.
- [ ] Sincronizar Markdown e índice na mesma transação e reverter ambos se qualquer etapa falhar.
- [ ] Deduplicar no índice repetições do mesmo usuário no mesmo campo sem alterar as ocorrências no Markdown.
- [ ] Renderizar Usuário disponível como `@{nome atual}`, preservando o Rótulo histórico no Markdown.
- [ ] Fornecer nome acessível e tooltip com tipo e nome atual, disponíveis no foco por teclado e no hover.
- [ ] Renderizar Usuário inexistente como `Menção a usuário: destino não encontrado`, sem link nem Rótulo histórico.
- [ ] Preservar Menções históricas após perda de elegibilidade e impedir sua reinserção como nova Menção enquanto a regra atual não for satisfeita.
- [ ] Limpar relações de saída quando a fonte for excluída logicamente ou o Comentário ficar inativo e reconstruí-las na restauração ou reativação.
- [ ] Remover relações de entrada após exclusão definitiva do Usuário sem reescrever o Markdown.
- [ ] Tornar `mentions:rebuild` idempotente sobre todas as fontes registradas, ignorando destinos irresolvíveis e sem tentar reconstruir autoria ou datas.
- [ ] Migrar todos os consumidores atuais de Menções a usuário para o novo módulo sem deixar chamadas aos relacionamentos ou módulos legados.
- [ ] Cobrir o comportamento pelos pontos de teste web, `MentionManager` com banco e Artisan, afirmando resultados observáveis em vez da organização interna.
