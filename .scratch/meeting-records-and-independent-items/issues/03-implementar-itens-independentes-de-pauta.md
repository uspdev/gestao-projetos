# 03 — Implementar Itens independentes de pauta

**O que construir:** permitir que colaboradores adicionem à Pauta um assunto com título próprio, sem projeto ou tarefa vinculada, mantendo ordenação, Anotações prévias do item, comentários, permissões e remoção consistentes com os demais itens.

**Blocked by:** 01 — Expandir a estrutura do banco com segurança.

**Status:** ready-for-agent

- [ ] Adicionar “Item independente” ao fluxo de criação junto das opções Projeto e Tarefa.
- [ ] Exibir o campo obrigatório “Título do item” quando o tipo independente for selecionado.
- [ ] Validar título entre 3 e 255 caracteres, aparar espaços e limpar o título quando a edição resultar em valor vazio conforme a regra definida.
- [ ] Permitir inserção em qualquer posição da Pauta e manter a sequência de ordem ao inserir ou remover itens.
- [ ] Permitir edição do título enquanto a reunião não estiver `COMPLETED`, bloqueando-a durante `COMPLETED` e reabrindo-a quando o status mudar.
- [ ] Permitir Anotações prévias do item com o comportamento atual de Markdown e limite de 10.000 caracteres.
- [ ] Permitir comentários em Itens independentes usando a autorização da reunião.
- [ ] Exibir o título próprio do Item independente na Pauta e nas notificações de comentários.
- [ ] Manter itens de Projeto e Tarefa existentes funcionando sem alteração e impedir conversão automática do Item independente.
- [ ] Exibir o texto “Adicionar item de pauta” junto do ícone `+`.
- [ ] Cobrir criação, edição, validação, autorização, status, ordenação, remoção, comentários, notificações e compatibilidade com itens legados em testes HTTP de funcionalidade.

