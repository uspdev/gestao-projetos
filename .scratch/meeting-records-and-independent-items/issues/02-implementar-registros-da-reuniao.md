# 02 — Implementar os registros da reunião

**O que construir:** permitir que colaboradores registrem e consultem Anotações prévias, Ata e Transcrição na reunião, com os limites, permissões, status e auditoria definidos na especificação.

**Blocked by:** 01 — Expandir a estrutura do banco com segurança.

**Status:** ready-for-agent

- [ ] Exibir o conteúdo legado de `notes` como Anotações prévias gerais, preservando seus dados e o preenchimento na criação da reunião.
- [ ] Exibir Anotações prévias gerais imediatamente acima da Pauta, com edição própria e limpeza persistindo `NULL`.
- [ ] Permitir edição da Ata em texto simples, com limite de 10.000 caracteres e possibilidade de revisão após `COMPLETED`.
- [ ] Permitir edição da Transcrição em texto simples, com limite de 100.000 caracteres e possibilidade de revisão após `COMPLETED`.
- [ ] Rejeitar valores acima dos limites sem truncamento silencioso e preservar quebras de linha.
- [ ] Permitir consulta pelos visualizadores autorizados e edição somente por colaboradores autorizados.
- [ ] Manter Anotações prévias bloqueadas em `COMPLETED` e editáveis novamente quando a reunião for reaberta.
- [ ] Exibir Ata e Transcrição próximas em um bloco de Registro da reunião abaixo da Pauta, fora do formulário geral de edição.
- [ ] Auditar alterações da Ata com conteúdo e registrar alterações da Transcrição apenas com usuário, data e metadados mínimos, sem replicar seu texto bruto no `activity_log`.
- [ ] Cobrir o comportamento por testes HTTP de funcionalidade, incluindo autorização, status, limites, limpeza, visualização, persistência e auditoria observável.

