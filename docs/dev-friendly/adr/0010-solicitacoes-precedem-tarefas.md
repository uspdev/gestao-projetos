# Solicitações precedem Tarefas

**Status:** aceito

As entradas enviadas por Sistemas clientes criarão Solicitações para avaliação,
e não Tarefas diretamente. Somente uma Solicitação aceita dará origem a uma
nova Tarefa no Projeto de escopo da Chave.

## Contexto

Nem toda correção ou melhoria proposta por um sistema externo deve entrar na fila de trabalho. O ciclo atual de Tarefas não representa rejeição nem uma resposta justificada ao Sistema cliente, e excluir uma Tarefa descartaria a distinção entre trabalho aceito e proposta recusada.

## Decisão

- A entrada externa será representada primeiro como Solicitação.
- Toda Solicitação criada pela API começará em `pending`. Estado, resposta,
  avaliador e Tarefa resultante serão controlados exclusivamente pelo servidor;
  o payload rejeitará tentativas de enviar esses campos.
- Uma Solicitação receberá título e descrição obrigatórios e poderá receber uma
  URL de origem opcional apontando para sua página no Sistema cliente.
- O título terá entre 3 e 120 caracteres, seguindo o limite das Tarefas.
- A descrição terá no máximo 10.000 caracteres. Depois da remoção de espaços
  nas extremidades, título e descrição deverão continuar não vazios.
- A URL de origem terá no máximo 2.048 caracteres, deverá ser absoluta e usará
  exclusivamente o esquema `http` ou `https`.
- A descrição da Solicitação será texto simples com quebras de linha, sem
  interpretação de HTML, Markdown ou Menções estruturadas.
- A URL de origem será apenas armazenada e apresentada ao avaliador; o backend
  do Gestão de Projetos não fará requisições para ela.
- A Solicitação pertencerá ao Sistema cliente autenticado e ao Projeto ao qual
  esse sistema está vinculado.
- A persistência usará o model `ProjectRequest`, com vínculos obrigatórios ao
  Projeto e ao Sistema cliente.
- O estado inicial será `pending`. `evaluated_by`, `evaluated_at` e `task_id`
  permanecerão nulos até a avaliação.
- Uma Solicitação aguardará avaliação antes de originar trabalho no Projeto.
- A aceitação de uma Solicitação criará uma nova Tarefa e registrará o vínculo
  entre ambas.
- A Solicitação permanecerá registrada após a avaliação, seja aceita ou
  rejeitada.
- Uma Solicitação rejeitada não originará Tarefa e manterá a Resposta à
  Solicitação para consulta pelo Sistema cliente.
- Rejeitar uma Solicitação exigirá uma Resposta à Solicitação textual e não
  vazia, com no máximo 10.000 caracteres.
- Aceitar uma Solicitação permitirá uma Resposta à Solicitação opcional e
  sempre disponibilizará ao Sistema cliente a identificação e a URL da Tarefa
  criada.
- Solicitações pendentes não terão resposta final.
- Uma Solicitação pendente poderá ser avaliada uma única vez como aceita ou
  rejeitada; ambos serão estados finais na primeira versão.
- A aceitação criará no máximo uma Tarefa, mesmo diante de cliques ou
  requisições concorrentes, por meio de operação transacional.
- O vínculo `task_id` será único, impedindo que uma Tarefa seja registrada como
  resultado de mais de uma Solicitação.
- A Solicitação registrará o usuário local que realizou a avaliação e o
  momento em que ela ocorreu.
- O usuário local que aceitar a Solicitação será registrado como criador da
  Tarefa resultante. O Sistema cliente permanecerá registrado como origem na
  Solicitação, e o vínculo entre Solicitação e Tarefa preservará a
  rastreabilidade completa.
- Reabertura, mudança do resultado da avaliação e criação de outra Tarefa para
  a mesma Solicitação permanecerão fora do escopo.
- Os termos canônicos da avaliação serão Solicitação aceita e Solicitação
  rejeitada; “Solicitação confirmada” não será usado.
- Administradores e contribuidores locais do Projeto poderão listar,
  consultar, aceitar ou rejeitar Solicitações.
- Visualizadores, pessoas com acesso apenas herdado e administradores globais
  sem vínculo local no Projeto não poderão visualizar nem avaliar
  Solicitações. Um administrador global precisará ingressar localmente para
  participar da triagem.
- O envio de uma nova Solicitação e a aceitação de uma Solicitação existente
  exigirão que o módulo de Tarefas esteja habilitado.
- A tentativa de enviar uma Solicitação pela API enquanto o módulo estiver
  desabilitado responderá `409 Conflict` com o código
  `tasks_module_disabled`.
- A listagem, a consulta e a rejeição de Solicitações existentes continuarão
  disponíveis quando o módulo de Tarefas estiver desabilitado.
- Uma Solicitação pendente não poderá ser aceita enquanto o módulo permanecer
  desabilitado.
- A aceitação abrirá um formulário de criação de Tarefa preenchido inicialmente
  com o título e a descrição da Solicitação.
- Ao preencher a descrição Markdown da Tarefa, o texto simples da Solicitação
  será tratado apenas como conteúdo inicial; formatação e Menções dependerão da
  revisão e da autorização do avaliador.
- O avaliador poderá ajustar o título, a descrição, a prioridade, as datas, as
  tags, o responsável e o estado antes de criar a Tarefa, usando as mesmas
  opções e validações do fluxo atual de criação.
- O responsável continuará opcional e, quando informado, deverá ser
  administrador ou contribuidor local do Projeto, conforme a regra vigente
  para Tarefas.
- Abrir o formulário não alterará o estado da Solicitação. Cancelamento e
  falhas de validação a manterão pendente.
- A Solicitação será marcada como aceita somente quando a Tarefa for criada
  com sucesso; criação, vínculo, autoria e avaliação ocorrerão na mesma
  transação.
- O conteúdo original da Solicitação permanecerá preservado; a Tarefa
  representará o trabalho decidido pelo Projeto, não uma cópia obrigatoriamente
  idêntica do pedido externo.
- Sistemas clientes poderão criar, listar e consultar suas Solicitações pela
  API, mas não poderão alterá-las, excluí-las, avaliá-las ou criar Tarefas
  diretamente.
- O acompanhamento externo nesta versão será feito consultando a listagem ou o
  detalhe da Solicitação. Webhooks e callbacks permanecerão fora do escopo.
- Aceitação, rejeição e criação da Tarefa serão ações web de usuários locais
  autorizados no Projeto.
- Solicitações não terão exclusão lógica nem ação de exclusão. Seus estados
  finais serão preservados como histórico.
- Se o usuário avaliador for removido posteriormente, sua referência poderá
  ficar nula sem apagar o estado final, o momento da avaliação ou a resposta.

## Opções consideradas

- **Criar uma Tarefa imediatamente:** rejeitada porque misturaria propostas ainda não avaliadas com trabalho aceito e exigiria sobrecarregar os estados ou a exclusão de Tarefas para representar recusa.
- **Excluir Tarefas consideradas irrelevantes:** rejeitada porque impediria uma
  resposta de domínio clara ao Sistema cliente e reduziria a rastreabilidade
  da decisão.

## Consequências

O sistema precisará de persistência e de um fluxo de avaliação próprios para Solicitações. A permissão de escrita concedida ao Sistema cliente será para enviar Solicitações, não para criar Tarefas diretamente; a Tarefa será criada por uma ação interna posterior. O Sistema cliente poderá continuar consultando a mesma Solicitação depois da avaliação e, quando ela for aceita, descobrir a Tarefa resultante pelo vínculo persistido.

A primeira versão não exigirá referência externa única nem oferecerá garantia de idempotência para a criação. Um retry após timeout poderá criar Solicitações duplicadas; a avaliação humana será o controle compensatório para impedir que duplicatas originem Tarefas indevidas. Idempotência poderá ser adicionada posteriormente se o volume ou o comportamento dos clientes justificar o custo.

Uma biblioteca PHP cliente, um plugin com formulário reutilizável e interfaces
incorporáveis nos Sistemas clientes também permanecerão fora desta entrega. A
primeira versão fornecerá o servidor, a administração, a triagem e a
documentação do contrato HTTP; clientes compartilhados poderão ser construídos
em uma entrega posterior sobre esse contrato.
