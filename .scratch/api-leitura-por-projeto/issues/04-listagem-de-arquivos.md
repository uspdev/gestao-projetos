# 04 — Listagem de Arquivos

**O que construir:** permitir que uma Chave do Projeto descubra, filtre e pagine os Arquivos acessíveis por propriedade ou compartilhamento, sem duplicatas nem vazamento entre Projetos.

**Blocked by:** 01 — Chaves do Projeto e leitura do Projeto.

**Status:** ready-for-agent

- [ ] `GET /api/projects/{project}/files` exige `files.read` e retorna cada Arquivo uma única vez entre os pertencentes ao Projeto, às suas Tarefas, às Reuniões diretamente vinculadas e os explicitamente compartilhados com essas Reuniões.
- [ ] Arquivos ligados apenas a Projeto pai, subprojeto ou Reunião indireta não são incluídos. Caminhos por recurso excluído ou módulo desativado não autorizam leitura, mas um caminho alternativo ativo mantém o Arquivo visível; Arquivos diretos do Projeto continuam disponíveis.
- [ ] Cada item inclui UUID, nome exibido, extensão, tipo MIME, tamanho em bytes, data de upload e resumo do Proprietário do arquivo (Projeto com ID/slug/nome; Tarefa ou Reunião com ID/título). Nome original, Autor do arquivo, disco, caminho, miniatura e indicador do caminho de acesso não aparecem.
- [ ] A lista aceita `search` no nome exibido, `owner_type[]` (`project`, `task`, `meeting`), `mime_type[]` por correspondência exata e `uploaded_from`/`uploaded_to` inclusivos em ISO 8601; consulta inválida responde `422`.
- [ ] A ordem é data de upload e identificador decrescentes; `page`/`per_page` usam 20 por padrão, 1–100 por página e envelope `data`/`links`/`meta`.
- [ ] O item não anuncia uma URL de download indisponível; `download_url` será acrescentada quando o ticket 05 publicar a rota correspondente.
- [ ] Testes HTTP cobrem os quatro caminhos de descoberta, deduplicação, filtros, paginação, módulos desligados, exclusão lógica e isolamento.
