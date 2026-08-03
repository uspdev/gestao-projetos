# Guia de Markdown no Gestão de Projetos

## Arquivos relacionados

- [Exemplo completo de descrição de projeto](exemplo-markdown.md): texto digitado e pré-visualização esperada.
- [Diagrama do fluxo](../../dev-friendly/markdown/diagrama-markdown.md): camadas do navegador, Blade, Laravel e persistência.

## Markdown no sistema

O Gestão de Projetos usa Markdown para guardar textos estruturados em descrições,
anotações e comentários. Markdown é uma linguagem de marcação simples: o texto
é salvo com seus marcadores e convertido em HTML seguro quando é exibido.

Para aprender a sintaxe básica, consulte o [guia oficial do CommonMark](https://commonmark.org/help/).
O projeto também aceita a extensão GitHub Flavored Markdown (GFM), cuja
[especificação oficial](https://github.github.com/gfm/) documenta recursos como
checklists e tabelas.

## Onde o Markdown pode ser usado

| Campo | Perfil do editor | Limite |
| --- | --- | --- |
| Descrição de projeto | Completo | 10.000 caracteres |
| Descrição de tarefa | Completo | 10.000 caracteres |
| Anotações prévias de reunião | Completo | 10.000 caracteres |
| Anotações de item de pauta | Completo | 10.000 caracteres |
| Comentário | Compacto | 10.000 caracteres |

Os campos **Ata** e **Transcrição** de reunião continuam sendo texto simples.

## Como editar um texto

1. Abra a edição do projeto, tarefa, comentário, reunião ou item de pauta.
2. Digite o texto ou use a barra do editor para inserir a formatação.
3. Use a pré-visualização para conferir o resultado que será exibido.
4. Salve o formulário.

A pré-visualização não salva o conteúdo. As alterações só são persistidas quando
o formulário principal é salvo.

O editor enriquecido e o realce de blocos de código são carregados pelo
jsDelivr. Se o CDN estiver temporariamente indisponível, o campo permanece como
uma área de texto simples e pode ser salvo normalmente; conteúdos já renderizados
continuam legíveis, apenas sem realce de sintaxe.

## Menções

Digite `@` e parte do nome ou título que procura. O seletor permite alternar
entre **Todos**, **Pessoas**, **Projetos**, **Tarefas**, **Reuniões** e
**Arquivos**. Selecione uma opção por clique, `Enter` ou `Tab`; digitar o texto
sem selecioná-lo não cria uma Menção.

O editor insere uma identificação interna junto ao rótulo. Não a altere nem a
escreva manualmente: ela mantém a Menção ligada à entidade correta, mesmo quando
existem nomes ou títulos iguais.

| Destino | Exemplo inserido pelo editor |
| --- | --- |
| Pessoa | `@[Marina Silva](mention:user:42)` |
| Projeto | `@[Programa de modernização](mention:project:18)` |
| Tarefa | `@[Revisar protótipo](mention:task:42)` |
| Reunião | `@[Reunião de kickoff](mention:meeting:17)` |
| Arquivo | `@[Termo de abertura](mention:file:550e8400-e29b-41d4-a716-446655440000)` |

Na pré-visualização e no texto salvo, uma Menção disponível aparece como
`@Nome ou título` e abre o recurso correspondente. A busca e a abertura sempre
respeitam as permissões: criar uma Menção não concede acesso. Quando o leitor
não puder ver o destino, a aplicação mostra uma mensagem de falta de permissão;
quando ele não existir mais, informa que o destino não foi encontrado.

### Menções a Arquivo

Use o botão de arquivo da barra do editor ou o seletor de Menções para escolher
um Arquivo disponível no contexto atual. Em uma reunião, um Arquivo de outro
contexto pode exigir a ação **Compartilhar com a reunião e mencionar** antes de
ser inserido. Esse compartilhamento é explícito e pode ser removido depois.

Uma Menção a arquivo não concede acesso por si só. A pessoa ainda precisa ter
permissão para consultar o Arquivo. Arrastar, colar ou usar a sintaxe de imagem
do Markdown não envia arquivos: faça o envio pela área de Arquivos e depois
insira a Menção pelo editor.

## Links

Links são escritos com `[texto](destino)`. O destino pode ser interno ou externo:

| Tipo | Exemplo | Comportamento |
| --- | --- | --- |
| Projeto | `[Programa](/projects/programa-modernizacao)` | Abre uma página de projeto, sujeita às permissões da pessoa. |
| Tarefa | `[Revisar protótipo](/tasks/42)` | Abre uma tarefa, sujeita às permissões do projeto. |
| Reunião | `[Reunião de kickoff](/projects/programa-modernizacao/meetings/17)` | Abre uma reunião vinculada ao projeto. |
| Arquivo | `[Termo de abertura](/files/550e8400-e29b-41d4-a716-446655440000)` | Navega na mesma aba quando o card está na tela atual; abre a tela proprietária em nova aba nos demais casos. |
| Externo | `[Documentação institucional](https://www5.usp.br/)` | Abre um site externo em nova aba. |
| Âncora | `[Ir para a agenda](#agenda)` | Navega para o título ou elemento com a âncora `agenda` na mesma aba. |

Links internos usam caminhos relativos à aplicação, normalmente iniciados por
`/`, e continuam protegidos por autenticação e autorização. Links externos devem
usar `http://` ou `https://`. Protocolos como `javascript:`, `data:` e `mailto:`
não são transformados em links pelo renderer seguro.

Não use uma URL interna para conceder acesso: o link apenas aponta para um
recurso que a aplicação continuará protegendo.
