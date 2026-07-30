# 05 — Mencionar e compartilhar Arquivos pela sintaxe unificada

**O que construir:** transformar novas inserções de Arquivos em Menções estruturadas por UUID, integrando-as ao autocomplete, ao índice e à apresentação autorizada. Em Reuniões, o fluxo deve continuar distinguindo a Menção do Compartilhamento de arquivo com reunião.

**Bloqueado por:** 01 — Reestruturar Menções a usuário sobre o núcleo polimórfico.

**Status:** ready-for-agent

- [ ] Registrar Arquivo como Entidade mencionável sob o alias `file`, encapsulando o model técnico de mídia.
- [ ] Usar UUID público válido na sintaxe e resolver o ID interno apenas para a relação polimórfica.
- [ ] Rejeitar UUID malformado, Arquivo inexistente e Arquivo novo não permitido conforme as mensagens de validação definidas.
- [ ] Fazer novas seleções de Arquivo inserirem a sintaxe canônica de Menção em todos os contextos suportados.
- [ ] Manter links Markdown comuns existentes funcionando como links e fora do índice, sem conversão automática.
- [ ] Incluir Arquivos no autocomplete unificado, com grupo e filtro próprios.
- [ ] Limitar a pesquisa de Arquivos aos conjuntos contextuais já autorizados para Projeto, Tarefa, Comentário, Reunião e Item de pauta.
- [ ] Não ampliar o conjunto de Arquivos pela hierarquia de Projetos nem pela simples visibilidade global do autor.
- [ ] Inserir imediatamente Arquivo já acessível no contexto da Reunião.
- [ ] Apresentar **Compartilhar com a reunião e mencionar** para Arquivo elegível que ainda dependa de compartilhamento.
- [ ] Persistir o Compartilhamento de arquivo com reunião antes de inserir a sintaxe no editor e não tratar a Menção como mecanismo de autorização.
- [ ] Manter o compartilhamento após remoção da Menção e exigir a ação própria para revogá-lo.
- [ ] Renderizar Arquivo disponível como `@{Nome exibido atual}` e navegar pela rota autorizada de UUID.
- [ ] Usar tooltip e nome acessível com tipo e Nome exibido atual, preservando o Rótulo histórico no Markdown.
- [ ] Renderizar falta de permissão sem link quando o Arquivo existir, mas o leitor não tiver acesso pelo Proprietário ou compartilhamento.
- [ ] Renderizar destino não encontrado quando o Arquivo não existir ou seu Proprietário estiver indisponível.
- [ ] Remover relações de entrada na exclusão definitiva do Arquivo sem reescrever textos.
- [ ] Comprovar que renomear o Arquivo atualiza apresentação e acessibilidade, mas não altera o Rótulo histórico.
- [ ] Cobrir Arquivo próprio, contextual, compartilhado, compartilhável e não elegível pelos pontos web e do módulo.
- [ ] Cobrir no navegador tanto a seleção direta quanto a ação explícita de compartilhar e mencionar.
