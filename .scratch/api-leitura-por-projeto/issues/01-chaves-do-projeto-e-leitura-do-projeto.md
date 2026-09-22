# 01 — Chaves do Projeto e leitura do Projeto

**O que construir:** permitir que um administrador diretamente vinculado gerencie Chaves de API do próprio Projeto e que uma delas consulte o contexto desse Projeto pela API, sem depender de Sistema cliente.

**Blocked by:** Nenhum — pode iniciar imediatamente.

**Status:** ready-for-agent

- [ ] O Projeto é o Projeto proprietário direto das chaves sob o alias `project`; o gerenciador da biblioteca aparece em um único card das configurações e permite emitir, renovar e revogar credenciais sem recriar essas operações na aplicação.
- [ ] Somente administradores diretamente vinculados ao Projeto gerenciam suas chaves; contribuidores, visualizadores, administradores herdados e administradores globais não vinculados são recusados.
- [ ] `viewer` e `contributor` concedem temporariamente as mesmas abilities de leitura (`projects.read`, `meetings.read`, `tasks.read`, `files.read`), sem wildcard ou abilities de Solicitações; finalidades `integration` e `ai` não mudam autorização.
- [ ] Chaves são aceitas apenas por Bearer; credenciais ausentes, inválidas, expiradas ou revogadas não autenticam, e o fallback por query string permanece desabilitado.
- [ ] `GET /api/projects/{project}` exige `projects.read` e escopo idêntico ao Projeto proprietário; retorna tudo o que um visualizador vê na interface: dados gerais, visibilidade, herança, tipo, fase, Projeto pai, tags, módulos, membros e papéis, comentários, Arquivos, Links, Menções recebidas, subprojetos e Reuniões em cuja Pauta aparece, sem `modules.tasks_enabled`.
- [ ] Projetos inexistentes, excluídos ou fora do escopo respondem `404`; a exclusão lógica revoga chaves ativas e a restauração não as reativa.
- [ ] O formulário de alteração do slug alerta sobre a quebra de URLs quando houver chaves ativas, mas permite a mudança sem confirmação extra, alias ou redirecionamento.
- [ ] Testes HTTP cobrem gerenciamento web, autorização, ciclo de vida da chave, leitura do Projeto e aviso de slug. A coexistência temporária com o fluxo de desenvolvimento antigo mantém a suíte verde até os tickets de retirada.
