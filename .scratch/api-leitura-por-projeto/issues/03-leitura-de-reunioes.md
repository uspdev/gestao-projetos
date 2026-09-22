# 03 — Leitura de Reuniões

**O que construir:** permitir descobrir Reuniões vinculadas diretamente ao Projeto e consultar seus registros completos, com filtros e sem conceder acesso indireto a outros Projetos.

**Blocked by:** 01 — Chaves do Projeto e leitura do Projeto.

**Status:** ready-for-agent

- [ ] `GET /api/projects/{project}/meetings` e `/meetings/{meeting}` exigem `meetings.read`, owner igual ao Projeto da URL e vínculo direto da Reunião com esse Projeto; vínculo apenas ao Projeto pai não basta para subprojeto.
- [ ] A lista retorna ID, título, estado com valor/rótulo, data agendada, local, resumos dos Projetos vinculados, timestamps e URL web, sem Anotações prévias, Ata ou Transcrição.
- [ ] O detalhe acrescenta Anotações prévias, Ata e Transcrição integrais, Pauta ordenada com ID e posição, comentários ativos com IDs do comentário e autor, Arquivos e Links próprios ou compartilhados e Menções recebidas.
- [ ] Uma Reunião diretamente vinculada é devolvida integralmente mesmo quando também pertence ou faz referência a outro Projeto; essas referências não autorizam o detalhe de recursos externos. Arquivos incorporados contêm metadados e preservam o download separado.
- [ ] A lista inclui Reuniões concluídas e aceita `status[]`, `scheduled_from`, `scheduled_to` inclusivos em ISO 8601 e `search` em título/local sem diferenciar caixa. Pagina 20 por padrão, 1–100 por página, em `data`/`links`/`meta`, ordenada por `scheduled_at desc`, `id desc`.
- [ ] Filtros inválidos respondem `422`; Reunião inexistente, excluída ou fora do vínculo direto responde `404`; módulo desativado responde `409 meetings_module_disabled`, após verificar o escopo do Projeto.
- [ ] Testes HTTP cobrem ambos os papéis, representação, filtros, paginação, Reunião compartilhada, referência externa, subprojeto, exclusão lógica e módulo desativado.
