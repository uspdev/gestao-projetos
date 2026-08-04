# Markdown

Markdown é usado para guardar textos estruturados em descrições, anotações e
comentários. O texto original é preservado; a aplicação o converte em HTML
seguro somente quando precisa exibi-lo ou pré-visualizá-lo.

## Campos e comportamentos

| Campo | Perfil | Limite |
| --- | --- | ---: |
| Descrição de Projeto | Completo | 10.000 caracteres |
| Descrição de Tarefa | Completo | 10.000 caracteres |
| Comentário | Compacto | 10.000 caracteres |
| Anotações prévias de Reunião | Completo | 10.000 caracteres |
| Anotações prévias do item | Completo | 10.000 caracteres |

Ata e Transcrição continuam sendo texto simples. A pré-visualização não salva o
conteúdo: a alteração só é persistida quando o formulário é salvo.

O editor enriquecido é uma melhoria da área de texto. Se os ativos externos não
carregarem, o campo continua editável e salvável, mas sem a interface enriquecida
e sem o realce de código.

## Menções

Digite `@` e parte do nome ou título que procura. A visão inicial abre na aba
**Usuários** e permite alternar entre **Usuários**, **Projetos**, **Tarefas**,
**Reuniões** e **Arquivos**. O contexto atual aparece
primeiro quando a regra do destino permitir ampliação da busca. Selecione uma
opção por clique, setas e `Enter`, ou `Tab`; digitar o texto sem selecioná-lo não
cria uma Menção.

O tipo do destino é indicado pela aba ativa. Cada opção exibe somente seu nome
ou título, sem repetir o tipo em um prefixo ou em um tooltip.
Para tecnologias assistivas, o nome acessível mantém tipo e nome para distinguir
opções com o mesmo rótulo. Nomes longos são cortados visualmente após 50
caracteres, e a caixa mantém largura uniforme entre as abas.

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
quando ele não existir mais, informa que o destino não foi encontrado. O rótulo
histórico não é exposto nesses dois estados.

Menções não geram notificações, e-mails ou eventos de acompanhamento e não
criam uma tela de backlinks. Links Markdown comuns continuam sendo links e não
entram no índice de Menções; sintaxes dentro de código também não são
interpretadas como Menções.

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
