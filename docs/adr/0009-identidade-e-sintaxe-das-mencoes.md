# Identidade e sintaxe das menções

**Status:** aceito

Menções serão exclusivas para usuários e usarão a sintaxe `@[Nome](mention:user:ID)`, com o identificador numérico existente como identidade e o nome gravado como rótulo histórico legível. O editor criará essa sintaxe somente após seleção explícita no autocomplete — por clique, teclado ou `Tab` —, de modo que o autor nunca precise conhecer o ID e texto digitado sem seleção não gere Menção.

Na exibição, o renderizador resolverá o nome atual pelo ID sem reescrever o Markdown bruto; usuário removido ou não revelável aparecerá como `@Usuário indisponível`, sem link. O autocomplete oferecerá apenas membros diretamente vinculados ao contexto do texto, excluindo acesso meramente herdado para preservar a regra de que herança não torna alguém participante ativo nem destinatário de notificações.
