# 05 — Integrar Referências de arquivo e compartilhamento com Reuniões

**O que construir:** conectar seletores de Arquivos aos editores, inserir links Markdown por UUID e permitir que Reuniões persistam acesso explícito a Arquivos relacionados.

**Blocked by:** 04 — Expor Arquivos com autorização, cards e auditoria.

**Status:** ready-for-agent

- [ ] Implementar seletor contextual no botão Referência de arquivo, retornando somente Arquivos visualizáveis e permitidos para o campo de destino.
- [ ] Restringir Projeto aos próprios Arquivos; Tarefa aos próprios e aos do mesmo Projeto; Comentário ao conjunto do objeto comentado; Reunião e Item aos próprios e compartilhados.
- [ ] Não ampliar seletores por herança entre Projetos.
- [ ] Omitir seletor em novos Projetos e novas Reuniões ainda não persistidos; permitir Arquivos do Projeto na criação de Tarefa e do objeto na criação de Comentário.
- [ ] Inserir a sintaxe `[Nome exibido](/files/{uuid})`, sem reescrever rótulos depois de renomeação ou exclusão.
- [ ] Criar `meeting_file_shares` e relações/modelos com unicidade, usuário autor, datas e remoção em exclusões definitivas.
- [ ] Permitir como origem Arquivos de Projetos vinculados e de Tarefas incluídas na pauta, independentemente de status, desde que o usuário edite a Reunião e visualize a origem.
- [ ] Implementar **Compartilhar com a reunião e inserir** como operação que persiste o acesso antes de inserir o link.
- [ ] Conceder leitura dos Arquivos compartilhados a qualquer usuário que possa visualizar a Reunião, inclusive em Reuniões multiprojeto.
- [ ] Exibir Arquivos próprios e compartilhados no card da Reunião, distinguindo a origem.
- [ ] Implementar **Remover da reunião** para colaboradores da Reunião, sem excluir o Arquivo nem reescrever Markdown.
- [ ] Preservar compartilhamentos após alterações de pauta, Projetos, status e módulos; suspender na exclusão lógica da origem e recuperar na restauração.
- [ ] Cobrir matriz de seletores, formulários não persistidos, links quebrados, ausência de herança, autorização e respostas não encontradas em testes HTTP.
- [ ] Cobrir criação/revogação de compartilhamento, estabilidade histórica, exclusões e Reunião multiprojeto em testes HTTP e Dusk.

## Critérios de conclusão

- Uma referência comum nunca concede acesso por si só.
- Apenas o compartilhamento persistido amplia a audiência de um Arquivo para a Reunião.
- Remover o compartilhamento revoga o acesso adicional sem alterar a origem ou outros leitores autorizados pelo Proprietário.
