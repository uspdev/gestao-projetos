# 07 — Consolidar autocomplete, acessibilidade e reconstrução multítipo

**O que construir:** concluir a experiência integrada dos cinco destinos e validar que editor, apresentação, consultas e recuperação operacional se comportam como um único módulo. Ao final, a funcionalidade deve estar pronta para uso sem introduzir notificações ou uma tela de backlinks.

**Bloqueado por:** 04 — Mencionar Reuniões com rota contextual; 05 — Mencionar e compartilhar Arquivos pela sintaxe unificada; 06 — Preservar Menções ao duplicar entidades.

**Status:** ready-for-agent

- [ ] Abrir inicialmente na aba **Usuários** e oferecer filtros para Usuários, Projetos, Tarefas, Reuniões e Arquivos; na aba Projetos, mostrar primeiro o contexto e ampliar a busca para Projetos acessíveis quando o autor digitar.
- [ ] Manter a prioridade contextual aprovada em cada tipo de fonte e permitir ampliação somente para os tipos autorizados.
- [ ] Identificar claramente o tipo durante a seleção mesmo quando destinos compartilham o mesmo rótulo.
- [ ] Permitir seleção explícita por clique, setas com `Enter` e `Tab`, sem transformar texto digitado em Menção sem escolha.
- [ ] Tratar respostas assíncronas obsoletas e fechamento do seletor sem inserir destino incorreto.
- [ ] Exibir todas as Menções disponíveis como `@{rótulo atual}`.
- [ ] Exibir tooltip de tipo e rótulo no hover e no foco por teclado e fornecer nome acessível equivalente.
- [ ] Não adicionar interação específica para revelar o tipo em dispositivos de toque.
- [ ] Aplicar de forma uniforme as mensagens de falta de permissão e destino não encontrado, sem expor Rótulo histórico.
- [ ] Comprovar que consultas de saída e de entrada não concedem acesso e podem ser filtradas para o leitor atual.
- [ ] Reconstruir de forma idempotente relações para todos os tipos de fonte e destino registrados.
- [ ] Preservar destinos excluídos logicamente, ignorar destinos definitivamente irresolvíveis e recuperar fontes restauradas ou Comentários reativados.
- [ ] Comprovar deduplicação por fonte, campo e destino e independência entre campos ou fontes diferentes.
- [ ] Cobrir sintaxes inválidas, tipos desconhecidos, chaves inválidas, links comuns e exemplos em código usando a mesma interpretação do Markdown.
- [ ] Executar testes web da experiência completa, testes de integração do `MentionManager`, testes Artisan e os fluxos Dusk definidos na especificação.
- [ ] Atualizar documentação de uso e operação para a nova linguagem de Menções e para a reconstrução multítipo.
- [ ] Remover referências técnicas remanescentes ao índice exclusivo de usuários ou a novas Referências de arquivo como links comuns.
- [ ] Confirmar que nenhuma Menção produz notificação, e-mail, evento de acompanhamento, associação ou compartilhamento implícito.
- [ ] Confirmar que nenhuma tela de backlinks ou consulta de “tudo que um usuário menciona” foi adicionada.
- [ ] Manter as suítes existentes de Markdown, Arquivos, Reuniões, duplicação e acompanhamento sem regressões.
