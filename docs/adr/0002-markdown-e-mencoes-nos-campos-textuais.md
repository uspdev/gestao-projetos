# Markdown e menções nos campos textuais

**Status:** aceito

> **Revisão posterior:** o ADR 0005 substitui a exclusividade de usuários e generaliza Menções para outros tipos de entidade. As demais decisões deste ADR permanecem válidas.

Esta decisão reúne a semântica dos campos Markdown e o modelo de Menções estruturadas. As **Anotações prévias da reunião** e as **Anotações prévias do item** serão interpretadas como Markdown, enquanto Menções a usuários terão identidade estável no texto e um índice derivado reconstruível, sem deixar de tratar o Markdown bruto como fonte editorial da verdade.

## Contexto

As Anotações prévias da reunião e dos itens representam o mesmo tipo de conteúdo preparatório e precisam oferecer uma experiência coerente de edição. Ao mesmo tempo, nomes digitados livremente não bastam para identificar de forma estável uma pessoa nem para permitir consultas futuras sobre quem foi mencionado.

Esta decisão altera pontualmente o ADR 0001, que determinava texto simples para as Anotações prévias da reunião. **Ata** e **Transcrição** permanecem textos simples, sem interpretação de Markdown ou HTML.

## Decisões

### Identidade, sintaxe e elegibilidade das Menções

- Menções serão exclusivas para usuários e usarão a sintaxe `@[Nome](mention:user:ID)`, com o identificador numérico existente como identidade e o nome gravado como rótulo histórico legível.
- O editor criará essa sintaxe somente após seleção explícita no autocomplete — por clique, teclado ou `Tab` —, de modo que o autor nunca precise conhecer o ID e texto digitado sem seleção não gere Menção.
- O autocomplete oferecerá apenas membros diretamente vinculados ao contexto do texto. Acesso meramente herdado não tornará alguém participante ativo elegível para Menção.
- No salvamento, somente IDs recém-adicionados serão validados contra o conjunto permitido. Menções históricas poderão permanecer se o usuário perder acesso posteriormente, evitando bloquear edições não relacionadas.

### Interpretação e exibição

- Menções serão reconhecidas na árvore de sintaxe abstrata produzida pelo parser, e não por expressões regulares aplicadas ao HTML.
- Na exibição, o renderizador resolverá o nome atual pelo ID sem reescrever o Markdown bruto.
- Usuário removido ou não revelável aparecerá como `@Usuário indisponível`, sem link.
- Notificações por qualquer canal permanecem fora do escopo desta decisão.

### Índice derivado

- O Markdown bruto permanecerá como fonte da verdade, mas a primeira implementação incluirá uma tabela `mentions` como índice derivado.
- Cada vínculo identificará a fonte polimórfica, o campo textual, o usuário mencionado e o autor que criou a Menção, com unicidade por fonte, campo e usuário. Ocorrências repetidas no mesmo campo produzirão um único vínculo.
- A sincronização ocorrerá transacionalmente em cada salvamento: vínculos novos serão criados, vínculos ausentes no novo texto serão removidos e os existentes serão preservados.
- Um comando reconstruirá o índice a partir dos textos, e remoções de fontes também limparão seus vínculos.
- Notificações por qualquer canal, caixa de entrada, backlinks e telas de consulta ficam fora do escopo inicial, evitando transformar o índice derivado em uma segunda fonte editorial ou introduzir um subsistema de notificações nesta entrega.

## Opções consideradas

- **Tratar nomes digitados como Menções:** rejeitada porque criaria semântica acidental e não forneceria identidade estável.
- **Reconhecer Menções no HTML renderizado:** rejeitada porque acoplaria a regra de domínio à saída do renderizador e ampliaria o risco de interpretações incorretas.
- **Usar a tabela `mentions` como fonte editorial:** rejeitada porque o conteúdo persistido precisa continuar autocontido no Markdown e o índice deve poder ser reconstruído.

## Consequências

A edição e a renderização dos conteúdos preparatórios passam a compartilhar a mesma semântica Markdown, e Menções ganham identidade consultável sem introduzir uma segunda fonte da verdade. Em contrapartida, todo salvamento de um campo com Menções deverá manter o índice derivado sincronizado, e a autorização de novas Menções deverá considerar o contexto específico do texto.
