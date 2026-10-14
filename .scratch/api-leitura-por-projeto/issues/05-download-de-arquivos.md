# 05 — Download de Arquivos

**O que construir:** permitir que uma Chave do Projeto baixe o conteúdo original de um Arquivo listado, com a mesma regra de visibilidade e cabeçalhos seguros.

**Blocked by:** 04 — Listagem de Arquivos.

**Status:** ready-for-agent

- [ ] `GET /api/projects/{project}/files/{uuid}` exige `files.read` e aplica exatamente a mesma regra de escopo e caminhos de acesso da listagem; UUID inexistente, Arquivo fora do escopo ou sem caminho ativo responde `404`.
- [ ] O endpoint entrega bytes do Arquivo, não JSON de metadados ou miniatura, com `Content-Type`, `Content-Length`, nome seguro em `Content-Disposition: attachment` e `X-Content-Type-Options: nosniff`.
- [ ] Imagens e PDFs também são apresentados como download; o nome original, o caminho físico e o disco não são revelados em corpo ou cabeçalhos.
- [ ] A listagem passa a incluir `download_url` apontando para essa rota, sem criar um endpoint adicional de conteúdo.
- [ ] Um Arquivo compartilhado com Reunião diretamente vinculada é baixável mesmo quando pertence a outro recurso; Arquivos de módulos desligados ou recursos excluídos só são baixáveis se houver outro caminho ativo.
- [ ] Testes HTTP com armazenamento de teste verificam bytes, cabeçalhos, Bearer, UUID, compartilhamentos, deduplicação de caminhos e isolamento entre Projetos.
