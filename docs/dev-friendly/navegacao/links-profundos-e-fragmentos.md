# Links profundos e fragmentos de navegação

Este documento explica como o sistema gera links para uma entidade ou para um
item específico dentro de uma página. O mecanismo é usado por cards, Menções,
notificações, e-mails e redirects após formulários.

O código principal está em:

- [`DeepLink`](../../../app/Support/Navigation/DeepLink.php), que define os
  fragmentos canônicos e monta URLs;
- [`app/helpers.php`](../../../app/helpers.php), que expõe os helpers
  `deep_link()` e `deep_link_fragment()`;
- [`deep-links.js`](../../../resources/js/deep-links.js), que localiza, expande,
  rola e foca o destino no navegador;
- [`file-actions.js`](../../../resources/js/file-actions.js), que trata o caso
  especial de Arquivos e Links dentro de abas.

## O que é um fragmento

Um fragmento é a parte da URL depois de `#`. Ele só deve existir quando precisa
localizar algo além do recurso que a rota já identifica:

~~~text
/tarefas/12#comment-45
/projetos/demo/reunioes/8#meeting-item-31
~~~

Ele não é enviado ao servidor. Para a URL abaixo, o servidor recebe apenas o
caminho e a query string:

~~~text
/projetos/demo?view=subprojects#project-10
~~~

O servidor usa `view=subprojects` para montar a página. Depois que o HTML é
carregado, o navegador e o JavaScript usam `#project-10` para localizar o
elemento com `id="project-10"`.

Por isso, quando um link profundo for necessário, suas duas partes precisam ser
compatíveis:

~~~text
URL:  /tarefas/12#comment-45
HTML: <li id="comment-45" ...>
~~~

O fragmento não é uma chave estrangeira, não faz consulta no banco e não
substitui a rota. Ele somente identifica um elemento já renderizado na página.
Se a rota já abre o recurso desejado, use apenas `route()`: `/tarefas/12` não
precisa de `#task-12`.

## Fragmentos canônicos disponíveis

[`DeepLink::fragment()`](../../../app/Support/Navigation/DeepLink.php#L40)
centraliza a identidade que uma entidade pode usar quando precisar ser
localizada dentro de uma página.

Estar nesta tabela **não significa que o fragmento será acrescentado a todo
link da entidade**. A tabela define IDs padronizados para elementos HTML e para
os casos em que uma tela contém vários itens. Quando a rota já abre a própria
entidade, o link continua usando somente `route()`.

| Entidade | Fragmento disponível | Identificador usado | Uso típico |
| --- | --- | --- | --- |
| Projeto | `project-12` | ID do Projeto | Localizar um card em listas de Projetos ou Subprojetos e preservar o foco após ações feitas nessas telas. Um link para a página do próprio Projeto não usa esse fragmento. |
| Tarefa | `task-20` | ID da Tarefa | Localizar um card no dashboard, em listas ou no Kanban e retornar ao card após uma ação contextual. Um link para a página da própria Tarefa não usa esse fragmento. |
| Reunião | `meeting-30` | ID da Reunião | Localizar um card em uma lista de Reuniões. Um link para a página da própria Reunião não usa esse fragmento. |
| Item de pauta | `meeting-item-40` | ID do Item de pauta | Abrir a Reunião e localizar um Item de pauta específico, inclusive quando ele estiver em uma área recolhida. |
| Comentário | `comment-50` | ID do Comentário | Abrir a página do Projeto, Tarefa ou Reunião que contém a conversa e localizar o Comentário exato. |
| Usuário | `user-60` | ID do Usuário | Localizar uma pessoa em listas de membros ou retornar a ela após uma ação de vínculo ou função. Um link para o perfil do próprio Usuário não usa esse fragmento. |
| Arquivo | `file-UUID` | UUID público do Arquivo | Abrir a página de seu Proprietário ou de uma Reunião compartilhada, selecionar a aba adequada e localizar o card do Arquivo. |
| Link | `link-UUID` | UUID público do Link | Abrir a página que contém o Link, selecionar a aba de Links e localizar seu card. |

Em resumo, Projeto, Tarefa, Reunião e Usuário possuem fragmentos porque também
podem aparecer como itens dentro de telas mais amplas. Esses fragmentos não
fazem parte de suas URLs normais:

~~~text
/projetos/demo
/tarefas/20
/projetos/demo/reunioes/30
/usuarios/60
~~~

Já um destino interno precisa do fragmento para distinguir qual elemento da
página deve receber rolagem e foco:

~~~text
/tarefas/20#comment-50
/projetos/demo/reunioes/30#meeting-item-40
/projetos/demo#file-UUID
~~~

Entidades que ainda não foram registradas no `match` de `DeepLink` não devem
inventar um fragmento localmente. O método lança uma exceção para revelar que a
entidade precisa ser adicionada ao contrato de navegação.

Arquivos e Links usam UUID, e não a chave numérica interna. Isso mantém a
identidade pública já usada por essas entidades sem expor a sequência do banco.

## Uso dos helpers

### `deep_link_fragment()` para o destino HTML

Use o helper quando o elemento da página deve ser o destino de uma referência:

~~~blade
<div
  id="{{ deep_link_fragment($task) }}"
  tabindex="-1"
  data-deep-link-target
>
  ...
</div>
~~~

Para uma Tarefa com ID 12, isso gera:

~~~html
<div id="task-12" tabindex="-1" data-deep-link-target>
~~~

`tabindex="-1"` permite que o JavaScript coloque foco no elemento sem incluí-lo
na navegação normal pelo teclado. `data-deep-link-target` informa ao
`deep-links.js` que o elemento pode ser localizado por um fragmento.

### `route()` para a própria entidade

Use a função normal do Laravel quando a rota já identifica o destino:

~~~blade
<a href="{{ route('tasks.show', $task) }}">
  {{ $task->title }}
</a>
~~~

O resultado não recebe um fragmento redundante:

~~~text
/tarefas/12
~~~

Isso vale para links de Projeto, Tarefa, Reunião e Usuário em cards, Menções,
notificações, e-mails e exportações.

Parâmetros de rota e de query continuam sendo usados normalmente:

~~~blade
{{ route('projects.meetings.show', [$project, $meeting]) }}
{{ route('projects.show', [$project, 'view' => 'subprojects']) }}
~~~

### `deep_link()` para um alvo interno

Quando a rota abre uma entidade, mas o destino é um item filho, informe
`target` explicitamente. O helper não infere um alvo, pois o fragmento só faz
sentido quando essa diferença é intencional:

~~~php
deep_link(
    'tasks.show',
    $task,
    target: $comment,
)
~~~

Resultado:

~~~text
/tarefas/12#comment-45
~~~

Esse padrão é usado por
[`MentionBacklinks`](../../../app/Services/Mentions/MentionBacklinks.php)
para apontar Menções ao Comentário ou ao Item de pauta exato, mantendo a rota
da entidade que contém o item.

### `withFragment()` em redirects

Quando uma ação POST/PUT/DELETE termina e deve retornar a uma área específica,
use o fragmento no redirect:

~~~php
return back()
    ->withFragment(deep_link_fragment($task))
    ->with('alert-success', 'Tarefa atualizada.');
~~~

Para uma seção fixa, use uma âncora contextual explícita:

~~~php
return back()->withFragment('task-description-' . $task->getKey());
~~~

Use uma âncora contextual quando o destino não é a entidade inteira, mas um
card ou seção específica:

~~~text
project-members
task-description-12
task-info-12
meeting-notes-30
meeting-record-30
meeting-agenda-30
~~~

`withFragment()` substitui um fragmento anterior da URL. Isso evita gerar uma
URL com dois `#` ou manter uma âncora antiga depois de uma ação.

## O fluxo no navegador

O arquivo `deep-links.js` é importado pelo ponto de entrada
[`resources/js/app.js`](../../../resources/js/app.js). Depois do
`DOMContentLoaded`, ele executa:

~~~text
window.location.hash
    -> remove o caractere #
    -> decodeURIComponent()
    -> document.getElementById()
    -> verifica data-deep-link-target
    -> abre ancestrais .collapse
    -> abre data-deep-link-expand, quando houver
    -> scrollIntoView()
    -> focus()
~~~

O mesmo processamento ocorre quando o hash muda ou quando o usuário clica em
um link interno da mesma página.

O JavaScript só processa links da mesma origem, caminho e query string no
tratamento de clique. Links para outra página continuam sendo tratados no
carregamento da nova página, quando `window.location.hash` é lido novamente.

## Itens dentro de áreas recolhidas

Um Item de pauta pode estar dentro de uma área Bootstrap recolhida. Nesse caso,
o elemento informa qual área adicional precisa ser aberta:

~~~blade
<li
  id="{{ deep_link_fragment($item) }}"
  data-deep-link-target
  data-deep-link-expand="{{ $notesCollapseId }}"
>
~~~

O JavaScript abre os ancestrais com classe `collapse` e depois abre o elemento
indicado por `data-deep-link-expand`. Só então faz o scroll e coloca o foco.

## Arquivos e Links

Arquivos e Links têm comportamento próprio porque podem estar em uma aba
escondida do componente de Arquivos.

O HTML usa os fragmentos canônicos:

~~~html
<article id="file-UUID" data-file-card data-deep-link-target>
<article id="link-UUID" data-link-card data-deep-link-target>
~~~

O `deep-links.js` ignora esses dois tipos para não disputar o processamento
com `file-actions.js`. O fluxo específico é:

~~~text
#file-UUID ou #link-UUID
    -> localiza o card
    -> verifica se a aba está escondida
    -> abre a aba correta
    -> aplica file-reference-highlight
    -> rola até o card
    -> coloca foco
    -> remove o destaque depois de 3 segundos
~~~

As abas também possuem fragmentos próprios, por exemplo:

~~~text
medias-imagens
medias-documentos
medias-links
~~~

Esses fragmentos identificam a aba. Já `file-UUID` e `link-UUID` identificam
um item específico dentro da aba.

Ao adicionar outro tipo de conteúdo ao navegador de Arquivos, é necessário
decidir se o destino deve ser tratado pelo `deep-links.js` geral ou pelo
`file-actions.js`. Não registre o mesmo comportamento nos dois lugares sem
garantir que a aba e o destaque não sejam processados duas vezes.

## Menções, notificações e e-mails

As URLs montadas para Menções são armazenadas na pendência de notificação e
usadas pelo resumo de acompanhamento. A própria entidade usa apenas sua rota;
uma origem interna usa fragmento para apontar para o item exato:

~~~text
Projeto:       /projetos/demo
Tarefa:        /tarefas/20
Reunião:       /projetos/demo/reunioes/30
Comentário:    /tarefas/20#comment-45
Item de pauta: /projetos/demo/reunioes/30#meeting-item-40
Arquivo:       /projetos/demo#file-UUID
~~~

O envio do e-mail e a navegação são responsabilidades diferentes:

~~~text
fila/worker decide quando o e-mail será enviado
fragmento decide onde a página abrirá após o clique
~~~

O servidor ainda precisa validar acesso à página e à origem da Menção. O
fragmento não concede acesso e não substitui as policies ou as validações dos
controllers.

## Atualização e exclusão

Quando a rota já abre a entidade, o redirect não precisa repetir sua identidade
em um fragmento:

~~~text
POST -> redirect /tarefas/20
~~~

Após atualizar um campo específico, pode apontar para o card daquele campo:

~~~text
POST -> redirect /tarefas/20#task-description-20
~~~

Após excluir o elemento, o redirect não deve apontar para um ID que deixou de
existir. Deve apontar para um contexto sobrevivente, por exemplo:

~~~text
/reunioes/30#meeting-agenda-30
/projetos/demo#project-members
/reunioes/30#files-meeting-30
~~~

## Checklist para adicionar uma nova entidade

Ao tornar uma nova entidade navegável:

1. Adicione a entidade ao `match` de `DeepLink::fragment()` e defina o formato
   canônico do fragmento.
2. Decida se a identidade pública deve usar ID ou UUID.
3. Adicione `id="{{ deep_link_fragment($model) }}"` ao elemento realmente
   selecionável na view.
4. Adicione `tabindex="-1"` e `data-deep-link-target` quando o destino for
   processado pelo `deep-links.js` geral.
5. Continue usando `route()` nos cards, Menções, notificações e e-mails quando
   a rota já abrir a própria entidade.
6. Use `deep_link()` com `target:` somente quando a rota abrir uma entidade,
   mas o link precisar apontar para um Comentário, Item de pauta ou outro filho.
7. Atualize redirects de criação, edição e exclusão com
   `withFragment()` quando o retorno precisar preservar o contexto.
8. Se o item estiver em uma aba ou componente especial, defina se o tratamento
   ficará no JavaScript geral ou no controlador específico desse componente.
9. Adicione testes para o fragmento, para a URL e para o destino HTML.

## Testes existentes

[`DeepLinkTest`](../../../tests/Unit/DeepLinkTest.php) verifica:

- o fragmento canônico de cada entidade registrada;
- a exigência de informar um `target` diferente da entidade da rota;
- a preservação dos parâmetros de query.

Os testes de Menções verificam que a própria entidade usa a rota sem fragmento
e que origens internas continuam navegando até o elemento específico.
