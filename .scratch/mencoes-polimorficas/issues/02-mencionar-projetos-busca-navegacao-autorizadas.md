# 02 — Mencionar Projetos com busca e navegação autorizadas

**O que construir:** permitir que um autor encontre e insira Menções a Projetos em qualquer fonte registrada, usando o ID estável na sintaxe e o nome e a rota atuais na leitura. O fluxo deve priorizar o contexto, respeitar autorização e impedir relações autorreferentes.

**Bloqueado por:** 01 — Reestruturar Menções a usuário sobre o núcleo polimórfico.

**Status:** ready-for-agent

- [ ] Registrar Projeto como Entidade mencionável sob o alias `project`.
- [ ] Inserir Menção a Projeto com ID numérico imutável, sem persistir slug ou nome como identidade.
- [ ] Incluir Projetos no autocomplete unificado, com grupo e filtro próprios e resultados identificados pelo tipo durante a seleção.
- [ ] Priorizar o Projeto do contexto e outros Projetos contextualmente relacionados antes dos demais Projetos visíveis ao autor.
- [ ] Pesquisar fora do contexto somente Projetos que o autor possa visualizar pela policy atual.
- [ ] Omitir e rejeitar Menção do Projeto para ele próprio em sua própria descrição.
- [ ] Permitir que fontes diferentes, inclusive Comentários, mencionem o Projeto do contexto quando fonte e destino não forem a mesma entidade.
- [ ] Indexar a relação com `target_type` de Projeto e fornecer consulta de entrada autorizável pelo destino.
- [ ] Renderizar Projeto disponível como `@{nome atual}` e navegar usando seu slug atual, sem reescrever o ID ou o Rótulo histórico no Markdown.
- [ ] Fornecer tooltip e nome acessível no formato de tipo e nome atual.
- [ ] Renderizar falta de autorização com a mensagem explícita confirmada, sem link nem Rótulo histórico.
- [ ] Preservar a relação quando o Projeto sofrer exclusão lógica e voltar a resolvê-la após restauração.
- [ ] Remover relações de entrada na exclusão definitiva, mantendo a sintaxe da fonte como destino não encontrado.
- [ ] Comprovar que renomear ou alterar o slug do Projeto modifica a apresentação e a rota, mas não altera textos existentes.
- [ ] Cobrir busca contextual e global autorizada, autorreferência, validação, sincronização, apresentação e ciclo de vida pelos pontos web e do módulo.
