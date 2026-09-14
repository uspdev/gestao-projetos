# Integrações por Chave de API e Solicitações

**Status:** ready-for-agent

## Declaração do problema

Sistemas externos e modelos LLM precisam consultar o contexto de um Projeto e
suas Tarefas e precisam encaminhar propostas de correção ou melhoria ao Gestão
de Projetos. Hoje a aplicação não possui uma API de negócio para esses usos,
nem uma identidade estável para cada sistema integrado.

Criar Tarefas diretamente a partir de conteúdo externo eliminaria uma etapa
necessária de avaliação humana. Propostas irrelevantes entrariam na fila de
trabalho e sua exclusão apagaria a justificativa fornecida ao sistema de
origem. Também é necessário impedir que uma credencial acesse outro Projeto ou
as Solicitações enviadas por outro sistema.

A biblioteca `uspdev/api-keys` recém-criada fornece emissão, renovação,
revogação, autenticação e autorização por abilities, mas deixa rotas de
negócio, owners, papéis, finalidades, respostas e regras de domínio sob
responsabilidade da aplicação consumidora.

## Solução

Instalar e configurar `uspdev/api-keys` e representar cada integração por um
Sistema cliente estável vinculado a exatamente um Projeto. O Sistema cliente
será owner das suas Chaves de API e das suas Solicitações, preservando sua
identidade quando uma credencial for renovada.

Expor uma API autenticada por Bearer para consultar o Projeto, listar e
consultar Tarefas e criar, listar e consultar Solicitações. O Projeto será
indicado explicitamente pelo `slug` em todas as rotas. As chaves poderão ter
papel `viewer` ou `contributor`; somente o segundo poderá enviar Solicitações.

Uma Solicitação será uma proposta em texto simples, inicialmente pendente. Um
administrador ou contribuidor com vínculo local no Projeto poderá rejeitá-la
com uma resposta ou aceitá-la por meio do fluxo normal de criação de Tarefa. A
aceitação criará no máximo uma Tarefa, registrará o avaliador como criador e
preservará o Sistema cliente como origem auditável.

## Histórias de usuário

1. Como administrador de Projeto, quero cadastrar um Sistema cliente, para identificar de forma estável uma integração externa.
2. Como administrador global, quero cadastrar Sistemas clientes em um Projeto, para apoiar a administração das integrações.
3. Como administrador de Projeto, quero dar um nome único por Projeto ao Sistema cliente, para distingui-lo das demais integrações.
4. Como administrador de Projeto, quero registrar uma descrição opcional do Sistema cliente, para documentar sua finalidade.
5. Como administrador de Projeto, quero editar nome e descrição sem trocar a identidade do Sistema cliente, para manter chaves e histórico vinculados.
6. Como administrador de Projeto, quero emitir uma Chave de API para um Sistema cliente, para permitir que ele se autentique.
7. Como administrador de Projeto, quero escolher entre as finalidades Integração e IA, para identificar o uso esperado da credencial.
8. Como administrador de Projeto, quero escolher entre os papéis `viewer` e `contributor`, para limitar as ações da credencial.
9. Como administrador de Projeto, quero tornar a expiração da chave opcional, para acomodar integrações sem política de rotação definida.
10. Como administrador de Projeto, quero ver o token somente no momento da emissão, para reduzir o risco de exposição posterior.
11. Como administrador de Projeto, quero renovar uma chave, para trocar a credencial sem perder a identidade e o histórico do Sistema cliente.
12. Como administrador de Projeto, quero que a renovação revogue a chave anterior, para impedir a permanência silenciosa de duas credenciais equivalentes.
13. Como administrador de Projeto, quero revogar uma chave, para interromper imediatamente seu acesso.
14. Como administrador de Projeto, quero manter Sistemas clientes sem chaves válidas como registros históricos, para preservar a origem das Solicitações.
15. Como administrador de Projeto, quero encontrar Sistemas clientes nas configurações do Projeto, para administrar integrações junto das demais configurações.
16. Como administrador de Projeto, quero abrir uma página própria para gerenciar as chaves de cada Sistema cliente, para não misturar credenciais de owners diferentes.
17. Como contribuidor ou visualizador, não quero poder administrar Sistemas clientes ou chaves, para preservar o controle administrativo das integrações.
18. Como pessoa com acesso apenas herdado, não quero poder administrar integrações de um subprojeto, para respeitar a exigência de vínculo local.
19. Como Sistema cliente, quero autenticar com um cabeçalho Bearer, para evitar colocar segredos em URLs.
20. Como Sistema cliente, quero que uma chave ausente, inválida, expirada ou revogada seja recusada, para ter um resultado de autenticação previsível.
21. Como Sistema cliente com papel `viewer`, quero consultar o Projeto, para obter seu contexto geral.
22. Como Sistema cliente com papel `viewer`, quero listar as Tarefas do Projeto, para conhecer o trabalho existente.
23. Como Sistema cliente com papel `viewer`, quero consultar uma Tarefa diretamente por ID, para recuperar seu contexto integral.
24. Como Sistema cliente com papel `viewer`, quero consultar minhas Solicitações, para acompanhar propostas já enviadas.
25. Como Sistema cliente com papel `viewer`, não quero poder enviar Solicitações, para que meu papel permaneça somente de leitura.
26. Como Sistema cliente com papel `contributor`, quero enviar uma Solicitação, para propor uma correção ou melhoria.
27. Como Sistema cliente, quero acessar somente o Projeto ao qual pertenço, para que uma credencial não atravesse limites de Projeto.
28. Como Sistema cliente, quero ver somente minhas próprias Solicitações, para que integrações diferentes no mesmo Projeto permaneçam isoladas.
29. Como Sistema cliente, quero receber recurso não encontrado ao tentar acessar conteúdo fora do meu escopo, para que a API não revele a existência de dados alheios.
30. Como Sistema cliente, quero que a leitura do Projeto inclua nome, slug, descrição, estado, tipo, fase, Projeto pai, tags e módulos, para obter contexto suficiente para automações e LLMs.
31. Como Sistema cliente, quero receber a descrição do Projeto em seu Markdown original, para preservar o conteúdo sem HTML renderizado.
32. Como Sistema cliente, quero saber se o módulo de Tarefas está habilitado, para entender quais operações estão disponíveis.
33. Como Sistema cliente, quero que a leitura do Projeto omita membros e regras internas de autorização, para reduzir a exposição de dados desnecessários.
34. Como Sistema cliente, quero que toda representação de Tarefa inclua sua descrição integral, para nunca tomar decisões com contexto incompleto.
35. Como Sistema cliente, quero receber estado e prioridade com valor técnico e rótulo, para processar e também apresentar esses dados.
36. Como Sistema cliente, quero conhecer datas, responsáveis, tags e URL web da Tarefa, para interpretar prazo, atribuição e contexto.
37. Como responsável por privacidade, quero que responsáveis de Tarefas sejam expostos apenas pelo nome, para não revelar e-mail ou número USP.
38. Como Sistema cliente, quero que a listagem de Tarefas inclua também as concluídas, para que “listar Tarefas” não esconda parte do histórico.
39. Como Sistema cliente, quero filtrar Tarefas por um ou mais estados, para consultar apenas o subconjunto relevante.
40. Como Sistema cliente, quero que Tarefas sejam ordenadas pelas atualizações mais recentes, para encontrar primeiro o trabalho alterado recentemente.
41. Como Sistema cliente, quero paginar Tarefas e Solicitações, para evitar respostas excessivamente grandes.
42. Como Sistema cliente, quero escolher entre 1 e 100 itens por página, para adaptar o consumo sem poder desabilitar a paginação.
43. Como Sistema cliente, quero informar título e descrição obrigatórios na Solicitação, para que a proposta tenha conteúdo suficiente para avaliação.
44. Como Sistema cliente, quero informar uma URL de origem opcional, para direcionar o avaliador à tela do sistema em que a proposta surgiu.
45. Como responsável por segurança, quero que a URL de origem seja apenas armazenada, para que o servidor não faça requisições a endereços fornecidos externamente.
46. Como Sistema cliente, quero que título, descrição e URL sejam validados sem truncamento, para corrigir envios incompatíveis com o contrato.
47. Como Sistema cliente, quero receber `201 Created`, a Solicitação integral e sua localização canônica, para começar a acompanhá-la imediatamente.
48. Como Sistema cliente, quero que estado, resposta, avaliador e Tarefa resultante sejam controlados pelo servidor, para não conseguir forjar uma avaliação.
49. Como Sistema cliente, quero listar Solicitações de todos os estados com as mais recentes primeiro, para acompanhar meu histórico.
50. Como Sistema cliente, quero filtrar Solicitações pendentes, aceitas ou rejeitadas, para consultar uma etapa específica do fluxo.
51. Como Sistema cliente, quero que uma Solicitação pendente não apresente resposta final, para distinguir ausência de avaliação de uma resposta vazia.
52. Como Sistema cliente, quero consultar a resposta de uma Solicitação rejeitada, para comunicar ao meu próprio usuário por que ela não virou trabalho.
53. Como Sistema cliente, quero descobrir a Tarefa resultante de uma Solicitação aceita, para acompanhar o trabalho incorporado pelo Projeto.
54. Como Sistema cliente, quero consultar a URL web da Solicitação, para fornecer um caminho à interface interna quando apropriado.
55. Como administrador ou contribuidor local, quero ver a fila de Solicitações junto das Tarefas, para avaliar propostas no contexto do trabalho.
56. Como administrador ou contribuidor local, quero ver a quantidade de Solicitações pendentes junto ao título da fila, para perceber trabalho de triagem rapidamente.
57. Como visualizador, não quero ver a fila de Solicitações, para que propostas ainda não incorporadas permaneçam restritas aos responsáveis pela triagem.
58. Como administrador global sem vínculo local, não quero ver ou avaliar Solicitações, para que administração técnica de chaves não conceda acesso ao conteúdo da fila.
59. Como Avaliador da Solicitação, quero rejeitar uma proposta com resposta obrigatória, para preservar e devolver a justificativa da decisão.
60. Como Avaliador da Solicitação, quero aceitar uma proposta por meio do formulário normal de Tarefa, para revisar os dados antes de incorporá-la ao trabalho.
61. Como Avaliador da Solicitação, quero receber título e descrição previamente preenchidos, para evitar copiar manualmente o conteúdo original.
62. Como Avaliador da Solicitação, quero ajustar título, descrição, prioridade, datas, tags, responsável e estado, para transformar a proposta em trabalho adequado ao Projeto.
63. Como Avaliador da Solicitação, quero cancelar o formulário sem aceitar a Solicitação, para poder abandonar uma avaliação incompleta com segurança.
64. Como Avaliador da Solicitação, quero que erros de validação mantenham a Solicitação pendente, para corrigir a Tarefa sem registrar uma aceitação falsa.
65. Como Sistema cliente, quero que o pedido original permaneça inalterado depois da aceitação, para consultar exatamente o conteúdo enviado.
66. Como equipe do Projeto, quero que a Tarefa registre como criador o usuário local que a aceitou, para preservar responsabilidade humana.
67. Como equipe do Projeto, quero preservar o Sistema cliente como origem da Solicitação, para manter rastreabilidade até a integração externa.
68. Como equipe do Projeto, quero que cada Solicitação seja avaliada uma única vez, para evitar resultados contraditórios.
69. Como equipe do Projeto, quero que uma Solicitação aceita crie no máximo uma Tarefa, para resistir a cliques repetidos ou requisições concorrentes.
70. Como equipe do Projeto, quero preservar Solicitações aceitas e rejeitadas sem exclusão, para manter o histórico das decisões.
71. Como Avaliador da Solicitação, quero continuar consultando e rejeitando Solicitações existentes quando o módulo de Tarefas estiver desabilitado, para não perder a capacidade de encerrar propostas inadequadas.
72. Como Avaliador da Solicitação, não quero aceitar uma Solicitação enquanto o módulo de Tarefas estiver desabilitado, para não criar trabalho em um módulo indisponível.
73. Como Sistema cliente, quero receber um conflito explícito quando tentar ler Tarefas ou criar Solicitação com o módulo desabilitado, para distinguir estado do Projeto de falha de autorização.
74. Como administrador de Projeto, quero ser avisado de que alterar o slug quebra URLs da API, para poder atualizar os Sistemas clientes conscientemente.
75. Como administrador de Projeto, quero que o aviso ganhe destaque quando houver Sistemas clientes, para perceber o impacto externo da alteração.
76. Como responsável por segurança, quero que excluir o Projeto revogue suas chaves, para que uma restauração não reative credenciais antigas silenciosamente.
77. Como equipe de manutenção, quero erros HTTP previsíveis e próximos dos padrões da biblioteca e do Laravel, para evitar uma camada de adaptação desnecessária.
78. Como equipe de manutenção, quero recursos de API explícitos, para que novos campos dos models não sejam expostos automaticamente.
79. Como equipe de manutenção, quero testes no nível HTTP usando o middleware real, para verificar o comportamento integrado observado pelos consumidores.
80. Como desenvolvedor de um Sistema cliente, quero documentação com payloads, respostas, filtros e erros, para integrar sem precisar ler o código da aplicação.

## Decisões de implementação

- O projeto passará a exigir PHP `^8.3` e instalará `uspdev/api-keys` com a restrição `^0.1`. O repositório irmão será somente uma referência e não será configurado como dependência local do tipo `path`.
- A configuração e as migrations fornecidas pelo package serão publicadas conforme seu processo oficial. As rotas POST de emissão, renovação e revogação e o componente de gerenciamento serão reutilizados, não reimplementados.
- Sistema cliente será implementado como `ClientSystem`, persistido em `client_systems`, e usará o alias estável `client-system` como owner do package.
- `ClientSystem` pertencerá a exatamente um Projeto. Terá nome obrigatório de 3 a 120 caracteres e único dentro do Projeto, descrição opcional em texto simples de até 1.000 caracteres, autoria de criação e atualização e timestamps.
- Alterar nome ou descrição não mudará a identidade, as chaves ou o histórico. URL, contato, estado ativo, callback, desativação e exclusão não integrarão a primeira versão.
- Administradores locais do Projeto e administradores globais poderão criar e editar Sistemas clientes e emitir, renovar ou revogar suas chaves. Contribuidores, visualizadores e pessoas com acesso apenas herdado não poderão administrar integrações.
- A expiração de uma chave será opcional. Quando informada, deverá estar no futuro. Renovar criará uma nova credencial e revogará a anterior segundo o comportamento do package.
- A exclusão lógica do Projeto revogará todas as chaves ativas de seus Sistemas clientes. Sistemas clientes e Solicitações permanecerão como histórico. Uma restauração exigirá novas chaves.
- `ClientSystem` resolverá as abilities das chaves. `viewer` concederá `projects.read`, `tasks.read` e `requests.read`. `contributor` concederá essas abilities e `requests.create`. Papel desconhecido não concederá abilities; não haverá `administrator` nem wildcard.
- As finalidades serão `integration` (“Integração”) e `ai` (“IA”). Elas serão apenas metadados e não mudarão abilities, rotas ou respostas.
- A credencial será aceita exclusivamente no cabeçalho `Authorization: Bearer`. O fallback por query string permanecerá desabilitado.
- O limitador atual do grupo de API será mantido em 60 requisições por minuto por IP. Sistemas clientes no mesmo IP compartilharão a cota; não haverá limitador específico por chave nesta versão.
- As rotas de negócio usarão `/api` sem segmento inicial de versão. Uma futura mudança incompatível deverá introduzir uma superfície versionada ou uma migração explícita dos consumidores.
- O Projeto será identificado pelo slug explícito da URL. A aplicação validará que o Sistema cliente autenticado pertence exatamente ao Projeto indicado.
- Tarefas e Solicitações usarão IDs numéricos. Uma Tarefa deverá pertencer ao Projeto da URL. Uma Solicitação deverá pertencer simultaneamente ao Projeto e ao Sistema cliente autenticado. Divergências serão respondidas como recurso não encontrado.
- A leitura de Projeto será `GET /api/projects/{project}`.
- A resposta do Projeto incluirá ID, slug, nome, descrição Markdown original, estado com valor e rótulo, tipo, fase, resumo do Projeto pai, tags, módulos habilitados, URL web e timestamps. Não incluirá membros, papéis humanos, visibilidade, herança nem campos de auditoria.
- A listagem de Tarefas será `GET /api/projects/{project}/tasks`; o detalhe será `GET /api/projects/{project}/tasks/{task}`.
- Toda representação de Tarefa, inclusive na listagem, incluirá ID, título, descrição Markdown integral, estado e prioridade com valor e rótulo, datas de início, vencimento, conclusão, criação e atualização, responsáveis somente pelo nome, tags e URL web.
- Comentários, arquivos, links, Menções, auditoria e vínculo de origem com Solicitação não serão expostos pelas rotas de Tarefa.
- A listagem de Tarefas incluirá todas as não excluídas, inclusive concluídas, ordenadas por atualização e ID decrescentes. Aceitará filtro por um ou mais valores atuais de `TaskStatus`.
- Solicitação será implementada como `ProjectRequest`, persistida em `project_requests`, com estados `pending`, `accepted` e `rejected` representados por `ProjectRequestStatus`.
- `ProjectRequest` terá vínculos obrigatórios com Projeto e Sistema cliente, título, descrição, URL de origem opcional, estado, resposta, avaliador, momento da avaliação, Tarefa resultante única e timestamps.
- O título da Solicitação será obrigatório entre 3 e 120 caracteres. A descrição será obrigatória em texto simples, não vazia após aparar as extremidades e limitada a 10.000 caracteres.
- `source_url` será opcional, absoluta, limitada a 2.048 caracteres e restrita a `http` ou `https`. Será apenas armazenada e exibida; o servidor nunca fará requisições a ela.
- A resposta da avaliação será texto simples com até 10.000 caracteres, obrigatória e não vazia na rejeição, opcional na aceitação e nula enquanto pendente.
- A criação será `POST /api/projects/{project}/requests`; listagem e detalhe serão `GET /api/projects/{project}/requests` e `GET /api/projects/{project}/requests/{request}`.
- A criação aceitará apenas título, descrição e URL de origem. Tentativas de enviar estado, resposta, avaliador ou Tarefa serão rejeitadas. O estado inicial será sempre `pending`.
- A criação bem-sucedida responderá `201 Created`, representação integral e cabeçalho `Location` apontando para o detalhe canônico.
- Toda representação de Solicitação incluirá ID, título, descrição integral, URL de origem, estado com valor e rótulo, resposta, timestamps de criação, atualização e avaliação, URL web e resumo da Tarefa quando aceita. A identidade do avaliador não será exposta externamente.
- A listagem de Solicitações mostrará somente as pertencentes ao Sistema cliente autenticado, todos os estados por padrão, ordenadas por criação e ID decrescentes. Aceitará filtro por `pending`, `accepted` e `rejected`.
- Listagens de Tarefas e Solicitações usarão paginação Laravel com 20 itens por padrão, `per_page` entre 1 e 100 e estrutura `data`, `links` e `meta`. Não haverá opção sem paginação.
- Com o módulo de Tarefas desabilitado, a leitura do Projeto continuará e indicará o estado. Leitura de Tarefas e criação de Solicitação responderão `409` com `code` igual a `tasks_module_disabled`. Solicitações existentes continuarão consultáveis e poderão ser rejeitadas, mas não aceitas.
- A administração de Sistemas clientes ficará na seção Integrações das configurações do Projeto. Cada Sistema cliente abrirá uma página própria que renderizará diretamente o gerenciador do package.
- A fila de Solicitações ficará junto às Tarefas e exibirá a quantidade pendente à esquerda do título. Somente administradores e contribuidores com vínculo local poderão listar, consultar, aceitar ou rejeitar. Visualizadores, acesso herdado e administradores globais sem vínculo local não verão a fila.
- Aceitar abrirá o formulário normal de criação de Tarefa, preenchido com título e descrição. O Avaliador poderá ajustar os demais campos e estará sujeito às validações e permissões atuais.
- Abrir ou cancelar o formulário e falhas de validação não mudarão a Solicitação. Aceitação, criação da Tarefa, autoria, sincronização das relações e vínculo ocorrerão na mesma transação.
- O Avaliador que aceitar será `created_by` da Tarefa. O conteúdo original e o Sistema cliente permanecerão preservados na Solicitação.
- Aceitação e rejeição serão terminais e ocorrerão uma única vez. Uma Solicitação aceita criará no máximo uma Tarefa, mesmo sob repetição ou concorrência. Não haverá reabertura, mudança de resultado ou segunda Tarefa.
- Solicitações não usarão exclusão lógica nem terão ação de exclusão. Se o usuário avaliador for removido, sua referência poderá ficar nula sem apagar estado, data ou resposta.
- As respostas `401` e `403` do middleware serão preservadas. `404` usará `message`; `409` usará `message` e `code`; `422` e `429` manterão os formatos convencionais do Laravel. Não haverá envelope global próprio.
- Alterar o slug continuará quebrando deliberadamente URLs antigas. O formulário avisará explicitamente que links da API precisarão ser atualizados e destacará o aviso quando houver Sistemas clientes. Não haverá confirmação adicional, redirecionamento nem alias de slug antigo.

## Decisões de teste

- O ponto de teste principal será o teste de funcionalidade HTTP do Laravel com banco de teste, atravessando rotas, middleware real do package, autorização, validação, persistência, transações, recursos JSON e renderização das páginas.
- Os testes observarão comportamento público: status e cabeçalhos HTTP, corpo JSON, conteúdo HTML, autorização, dados persistidos e efeitos transacionais. Não testarão métodos privados, estrutura de queries ou detalhes internos de controllers.
- O ponto principal cobrirá configuração do owner, resolução de abilities, credenciais ausentes, inválidas, expiradas e revogadas e os papéis `viewer` e `contributor`.
- O ponto principal cobrirá isolamento por Projeto e Sistema cliente, inclusive respostas que não revelem recursos fora do escopo.
- Serão testados campos expostos, ausência de dados pessoais e internos, descrições integrais, paginação, ordenação e filtros.
- Serão testados payload permitido, rejeição de campos controlados, limites textuais, URL de origem, `201`, `Location` e estado inicial da Solicitação.
- Serão testados módulo de Tarefas desabilitado, visualização interna, permissões por vínculo local e indisponibilidade da fila para visualizadores, acesso herdado e administrador global sem vínculo.
- Serão testadas rejeição, aceitação, cancelamento, falha de validação, preservação do pedido original, autoria da Tarefa e resposta externa após avaliação.
- Repetição e concorrência serão exercitadas no maior nível HTTP viável e apoiadas por restrições de persistência para comprovar que apenas uma Tarefa pode resultar.
- A exclusão de Projeto será testada como operação observável que revoga chaves e impede que uma restauração recupere credenciais antigas.
- O aviso de alteração de slug será verificado pela resposta HTML da página; não haverá teste Dusk apenas para conteúdo estático.
- O componente de gerenciamento será verificado no limite da integração da aplicação. Hashing, geração de segredos e demais detalhes internos não serão duplicados, pois pertencem à suíte do package.
- O repositório já usa testes de funcionalidade Laravel para políticas, validações, exposição de recursos e persistência; esse será o padrão reutilizado.

## Fora do escopo

- Criação direta de Tarefas por Sistemas clientes.
- Alteração, exclusão, aceitação ou rejeição de Solicitações pela API externa.
- Identidade individual de usuários dos Sistemas clientes, `requester_reference` ou filtro por solicitante humano.
- Idempotency key, referência externa única ou deduplicação automática. Um retry após timeout poderá criar Solicitações duplicadas, mitigadas inicialmente pela triagem humana.
- Reabertura de Solicitações, mudança do resultado ou associação de mais de uma Tarefa.
- Desativação, exclusão ou suspensão em lote de Sistema cliente.
- Webhooks, callbacks e notificações ativas ao Sistema cliente.
- Biblioteca PHP `projetos-api-client`, plugin, formulário reutilizável ou interface incorporável nos sistemas externos.
- Diferença de resposta ou autorização baseada em `purpose`.
- Papel `administrator`, wildcard de abilities ou herança automática de `ProjectUserRole`.
- Chave em query string, autenticação de usuário humano pelas chaves ou chave global para vários Projetos.
- Rate limit por Chave de API.
- Versionamento `/v1` inicial, redirecionamento de slugs antigos ou aliases de Projeto.
- Consulta externa de comentários, arquivos, links, Menções, membros, auditoria ou regras internas de permissão.
- Busca remota ou validação ativa da URL de origem.
- Execução operacional de migrations ou implantação em produção.

## Observações adicionais

- As decisões detalhadas estão registradas nos ADRs 0008 a 0019 e no glossário de domínio.
- O package autentica e anexa a Chave de API à requisição, mas não autentica um `User` e não verifica que o owner corresponde ao Projeto da URL. Essa conferência continua sendo responsabilidade obrigatória da aplicação.
- As abilities informadas no middleware usam lógica OR. Cada rota desta entrega deverá declarar explicitamente ao menos uma ability adequada, evitando combinações acidentais.
- O ambiente local analisado usa PHP 8.3.33 e Laravel 12. Ambientes em PHP 8.2 precisarão ser atualizados antes da instalação.
- A API é geral para Sistemas clientes. Modelos LLM são um tipo de consumidor, não uma identidade ou um contrato separado.
