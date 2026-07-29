# Guia de Markdown no Gestão de Projetos

## Arquivos relacionados

- [Exemplo completo de descrição de projeto](exemplo-markdown.md): texto digitado e pré-visualização esperada.
- [Diagrama do fluxo](diagrama-markdown.md): camadas do navegador, Blade, Laravel e persistência.

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

## Menções

Digite `@` e parte do nome da pessoa, depois selecione uma das opções exibidas.
Também é possível usar o botão de menção da barra do editor. Somente usuários
elegíveis no contexto atual aparecem na busca.

O editor insere uma identificação interna junto ao nome. Não altere nem escreva
essa identificação manualmente: ela mantém a menção vinculada à pessoa correta
mesmo quando existem nomes iguais.

Exemplo digitado:

```markdown
Responsável pela validação: @[Marina Silva](mention:user:42)
```

Na pré-visualização, aparece `Responsável pela validação: @Marina Silva`, com o
nome ligado ao perfil quando a pessoa está disponível. Se ela não estiver mais
disponível, o sistema mostra `@Usuário indisponível` sem criar um link.

## Referências de arquivos

Use o botão de arquivo da barra do editor e escolha um arquivo disponível no
contexto atual. O editor insere um link para o arquivo no texto.

Exemplo digitado pelo seletor:

```markdown
Consulte o [Termo de abertura](/files/550e8400-e29b-41d4-a716-446655440000).
```

Na pré-visualização, isso aparece como um link clicável com o texto **Termo de
abertura**. O UUID é ilustrativo; use o botão do editor para inserir o
identificador real.

A referência não concede acesso ao arquivo. A pessoa ainda precisa ter permissão
para consultá-lo. Em reuniões, compartilhe primeiro um arquivo de outro contexto
quando essa opção for necessária.

Arrastar, colar ou usar a sintaxe de imagem do Markdown não envia arquivos. Faça
o envio pela área de arquivos e insira a referência pelo seletor.

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
