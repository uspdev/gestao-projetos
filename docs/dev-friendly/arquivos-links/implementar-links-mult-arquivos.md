# Card de Arquivos com imagens, documentos e Links

## Resumo

Transformar o card atual em um navegador de recursos com abas permanentes: **Imagens | Documentos | Links**. Arquivos binários continuam em `media`; Links serão um recurso separado, com propriedade, permissões e compartilhamento próprios.

## Alterações principais

- Criar `Link` polimórfico para Projeto, Tarefa ou Reunião, com UUID, proprietário imutável, autor, rótulo editável e URL editável.
  - Aceitar somente URLs externas `http` e `https`, inclusive URLs que apontem para arquivos externos.
  - O rótulo inicial será a própria URL; não haverá consulta de título em sites externos.
  - Excluir o Link remove seus compartilhamentos; proprietário removido logicamente o torna inacessível, como ocorre com Arquivos.
  - Não reutilizar a tabela `media`: Links não têm binário, MIME, download ou miniatura.

- Criar compartilhamento de Links com Reuniões, paralelo ao compartilhamento de Arquivos.
  - Um Link de Projeto vinculado/na pauta ou de Tarefa na pauta poderá ser compartilhado por quem edita a Reunião e visualiza o Link.
  - O compartilhamento libera o Link aos visualizadores da Reunião sem transferir sua propriedade.
  - Na aba Links de uma Reunião, oferecer seleção dos Links elegíveis agrupados por origem e a ação de remover um Link já compartilhado.

- Atualizar o card:
  - Manter as três abas sempre visíveis, com contadores e estado vazio em cada uma; abrir inicialmente a primeira aba com conteúdo, seguindo a ordem Imagens, Documentos e Links.
  - Renomear a aba atual “Arquivos” para “Documentos”.
  - Exibir Links como linhas compactas reutilizando o padrão visual da lista: ícone de link, rótulo clicável, URL secundária truncada e ações de editar/excluir.
  - Abrir URLs em nova aba com `noopener noreferrer`.
  - Mapear extensões comuns de Documentos a ícones específicos: PDF, Word/ODT, Excel/CSV, PowerPoint, texto e arquivos compactados; usar ícone genérico nos demais casos.

- Substituir “Procurar” por “Enviar arquivos”.
  - O botão abre o seletor nativo com seleção múltipla e envia a seleção após a confirmação.
  - Processar Arquivos individualmente: salvar os válidos e retornar feedback com os nomes que falharam.
  - Preservar limite de 100 MB por Arquivo, autorização, miniaturas e auditoria já existentes.

- Adicionar o botão “Adicionar links”.
  - Abrir modal com textarea para colar uma URL por linha.
  - Ignorar linhas vazias; validar toda a lista antes de criar Links e preservar o conteúdo do modal se houver erro.
  - Criar todos os Links válidos da lista em uma operação e registrar criação, edição, exclusão, compartilhamento e revogação em auditoria.

## Interfaces e permissões

- Adicionar relações `links` nos proprietários e `sharedLinks` nas Reuniões, além da tabela de junção de compartilhamentos.
- Expor rotas de criação por proprietário, edição/exclusão por UUID e criação/remoção de compartilhamento em Reunião.
- Criar política de Links espelhando as regras de Arquivos: visualizadores leem; colaboradores criam; autor, admins do contexto e administradores globais editam/excluem; Tarefas concluídas bloqueiam criação, edição e exclusão.

## Testes

- Cobrir criação em lote, linhas vazias, URL inválida/protocolo não permitido, rótulo inicial, edição de rótulo e URL, exclusão e autorização.
- Cobrir seleção múltipla de Arquivos com sucesso parcial, limite por Arquivo e falha de miniatura sem afetar os demais.
- Cobrir compartilhamento e revogação de Links em Reuniões, elegibilidade de origem e inacessibilidade sem permissão.
- Atualizar testes de interface para abas permanentes, modal de Links, abertura segura de URL, ícones por extensão e novo fluxo de envio múltiplo.

## Premissas adotadas

- Links não são Menções e não entram no seletor de Arquivos do editor Markdown.
- Links duplicados podem existir quando criados em momentos distintos; duplicatas idênticas na mesma colagem serão criadas apenas uma vez.
- O envio automático após escolher Arquivos depende de JavaScript, como os comportamentos interativos atuais do card.
