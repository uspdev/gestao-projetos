# 03 — Mencionar Tarefas com prioridade contextual

**O que construir:** permitir que autores encontrem Tarefas por título e criem Menções navegáveis sem conhecer seus IDs. O autocomplete deve favorecer o Projeto atual, enquanto apresentação e histórico seguem a autorização e o estado da Tarefa.

**Bloqueado por:** 02 — Mencionar Projetos com busca e navegação autorizadas.

**Status:** ready-for-agent

- [ ] Registrar Tarefa como Entidade mencionável sob o alias `task`.
- [ ] Inserir Menção a Tarefa com ID numérico imutável e Rótulo histórico baseado no título selecionado.
- [ ] Incluir Tarefas no autocomplete unificado, com grupo e filtro próprios.
- [ ] Em fonte de Projeto ou Tarefa, sem termo, exibir apenas Tarefas dos Projetos contextuais; ao digitar, priorizar essas Tarefas antes de outras Tarefas ativas, visíveis e com o módulo habilitado.
- [ ] Em Comentário, herdar o contexto do objeto comentado para ordenar e limitar os primeiros resultados.
- [ ] Permitir pesquisa ampliada somente sobre Tarefas que o autor possa visualizar e cujo módulo esteja habilitado.
- [ ] Omitir e rejeitar Menção de uma Tarefa para ela própria em sua descrição.
- [ ] Indexar Tarefa como destino e oferecer consultas de entrada e saída sem conceder acesso.
- [ ] Renderizar Tarefa disponível como `@{título atual}` e navegar para sua rota atual.
- [ ] Tratar módulo desabilitado ou falta de visualização como falta de permissão, sem link nem Rótulo histórico.
- [ ] Tratar Tarefa excluída ou inexistente como destino não encontrado.
- [ ] Preservar a relação durante exclusão lógica e recuperá-la na restauração; remover a entrada após exclusão definitiva.
- [ ] Comprovar que alteração de título não reescreve Markdown e que apresentação, tooltip e nome acessível usam o título atual.
- [ ] Preservar Menções históricas quando o leitor ou editor perder acesso, validando novamente somente uma futura reinserção.
- [ ] Cobrir prioridade contextual, busca ampliada, autorreferência, policies, módulo, histórico e ciclo de vida pelos pontos web e do módulo.
