# 06 — Implementar Menções e índice derivado

**O que construir:** adicionar autocomplete de usuários, renderização segura da sintaxe de Menção e sincronização transacional da tabela derivada `mentions` em todos os campos Markdown.

**Blocked by:** 05 — Integrar Referências de arquivo e compartilhamento com Reuniões.

**Status:** ready-for-agent

- [ ] Criar `mentions` com fonte polimórfica, campo, usuário mencionado, autor da relação, datas, unicidade e índices definidos na especificação.
- [ ] Implementar serviço que extraia Menções da árvore de sintaxe abstrata e normalize ocorrências repetidas no mesmo campo.
- [ ] Criar busca autenticada de usuários elegíveis por contexto, seguindo o padrão de rotas e autorização existente e sem limite específico de requisições.
- [ ] Restringir resultados a membros diretos: Projeto; Projeto da Tarefa; união dos Projetos da Reunião; ou conjunto do objeto comentado. Excluir acesso apenas herdado e permitir o próprio autor.
- [ ] Conectar o botão/atalho de Menção aos dois perfis do editor e abrir busca ao digitar `@` mais texto.
- [ ] Permitir seleção por clique, setas com `Enter` ou `Tab`; não transformar texto digitado sem seleção.
- [ ] Inserir `@[Nome](mention:user:ID)` sem exigir que o autor conheça ou digite o ID.
- [ ] Resolver o nome atual na exibição sem reescrever o Markdown e emitir `@Usuário indisponível` sem link quando a identidade não puder ser revelada.
- [ ] Validar no salvamento apenas IDs recém-adicionados e preservar Menções históricas após perda de elegibilidade.
- [ ] Sincronizar o índice na mesma transação do texto, preservando autor/data de relações existentes e recriando relações removidas e adicionadas novamente.
- [ ] Limpar relações de fontes removidas/inativas e reconstruí-las no restore quando aplicável.
- [ ] Criar comando idempotente de reconstrução do índice para todos os campos Markdown e informar contagens de fontes, relações e erros.
- [ ] Não disparar notificações, e-mails ou alterações em mensagens existentes.
- [ ] Cobrir parser, sintaxe inválida, duplicatas, elegibilidade, validação incremental, indisponibilidade, transações, remoção/restauração e reconstrução com testes unitários e HTTP.
- [ ] Cobrir busca, seleção por mouse/teclado, texto não selecionado e exibição em Dusk.

## Critérios de conclusão

- O Markdown é suficiente para reconstruir integralmente `mentions`.
- Falha de sincronização impede o salvamento do texto, evitando divergência transacional.
- Nenhum canal de notificação ou e-mail é acionado por uma Menção.
