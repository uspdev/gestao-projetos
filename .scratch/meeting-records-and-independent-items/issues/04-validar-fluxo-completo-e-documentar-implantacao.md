# 04 — Validar o fluxo completo e documentar a implantação

**O que construir:** validar no navegador os fluxos principais da funcionalidade e entregar à equipe de implantação as instruções de publicação segura, sem executar o deploy.

**Blocked by:** 02 — Implementar os registros da reunião; 03 — Implementar Itens independentes de pauta.

**Status:** ready-for-agent

- [ ] Criar um fluxo Dusk que abra uma reunião autorizada, consulte Anotações prévias, Ata e Transcrição e valide edição, limpeza e limites essenciais.
- [ ] No mesmo fluxo ou em fluxo complementar, abrir o modal, selecionar Item independente, preencher seu título e confirmar a exibição de “Adicionar item de pauta”.
- [ ] Validar no navegador a exibição do Item independente na Pauta e das Anotações prévias do item.
- [ ] Executar a suíte de testes HTTP e Dusk relevante e corrigir regressões nos fluxos legados de Projeto, Tarefa e reuniões existentes.
- [ ] Documentar para a equipe responsável pela implantação a ordem migração → validação da estrutura → publicação do código.
- [ ] Documentar que a reversão da migração não deve ser executada quando houver dados novos e que a execução operacional da implantação está fora do escopo desta entrega.
