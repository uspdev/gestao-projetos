# 07 — Validar integração e documentar implantação

**O que construir:** validar os fluxos completos e entregar instruções operacionais para publicar editor, Arquivos, compartilhamentos e Menções com segurança, sem executar a implantação.

**Blocked by:** 06 — Implementar Menções e índice derivado.

**Status:** ready-for-agent

- [ ] Executar as suítes unitária, HTTP e Dusk relacionadas e corrigir regressões em Projeto, Tarefa, Comentário e Reunião.
- [ ] Validar de ponta a ponta edição → pré-visualização → salvamento → exibição segura nos cinco campos Markdown.
- [ ] Validar upload → processamento → card → referência → download → renomeação → exclusão, incluindo tarefa `DONE` e reunião `COMPLETED`.
- [ ] Validar Reunião multiprojeto com compartilhamento, audiência conjunta, revogação, mudança de pauta e exclusão lógica/restauração da origem.
- [ ] Validar autocomplete → Menção → índice → perda de elegibilidade → reconstrução, sem qualquer notificação ou alteração de e-mail.
- [ ] Executar testes de regressão para Ata e Transcrição como texto simples e para o conteúdo existente de Tipo de projeto.
- [ ] Documentar disk privado, variáveis de ambiente, limite de 100 MB, ajustes de PHP/proxy, GD, processador de fila, publicação dos ativos próprios compilados e acesso do navegador ao jsDelivr.
- [ ] Documentar a ordem de implantação: cópia de segurança e validação → dependências/ativos → migrações/configuração → processador de fila → código → conversão legada → verificações funcionais.
- [ ] Documentar plano de verificação e recuperação sem sugerir reversão destrutiva de migrações que já contenham Arquivos, compartilhamentos ou Menções.
- [ ] Verificar em homologação upload próximo de 100 MB, cabeçalhos de download, armazenamento não público e processamento assíncrono.
- [ ] Registrar explicitamente os riscos operacionais aceitos: ausência de antivírus e cotas, formatos gerais, links quebráveis, fila indisponível e consumo de armazenamento/backups.
- [ ] Não executar deploy, mudar infraestrutura, habilitar S3 nem alterar e-mails como parte deste ticket.

## Critérios de conclusão

- A documentação permite que a equipe de infraestrutura prepare e valide o ambiente sem inferir parâmetros ausentes.
- Os fluxos críticos possuem cobertura automatizada e uma lista reproduzível de verificação em homologação.
- A suíte Dusk registra acesso à internet como requisito e usa os ativos reais do jsDelivr sem cópias locais.
- As decisões fora de escopo permanecem inalteradas.
