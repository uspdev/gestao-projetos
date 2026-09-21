# API de leitura por Projeto

**Status:** ready-for-agent

## Declaração do problema

Integrações externas e modelos LLM precisam consultar o contexto de um Projeto,
suas Reuniões, Tarefas e Arquivos de modo granular, previsível e restrito ao
Projeto autorizado. O código de desenvolvimento atual associa Chaves de API a
Sistemas clientes e inclui um fluxo de Solicitações com escrita na API, mas a
entrada e a triagem de demandas pertencem ao sistema Chamados. Esse desenho
acrescenta uma identidade intermediária e duplica uma responsabilidade que não
deve existir no Gestão de Projetos.

Sem uma API de leitura completa, consumidores não conseguem descobrir Reuniões
ou Arquivos, filtrar coleções, paginar resultados nem recuperar o conteúdo de
um Arquivo com a mesma credencial usada para ler o Projeto. É necessário
assegurar que uma Chave de API de um Projeto não revele recursos de outro,
inclusive por vínculos entre Reuniões e compartilhamentos de Arquivos.

## Solução

Cada Projeto será proprietário direto de suas Chaves de API. Administradores
diretamente vinculados ao Projeto as gerenciarão nas configurações, usando o
componente da biblioteca já instalada. A API de negócio autenticada por Bearer
oferecerá somente `GET` para o Projeto e para listagens e detalhes de Reuniões,
Tarefas e Arquivos, sempre sob a URL com o `slug` explícito do Projeto.

As coleções terão filtros básicos, paginação convencional do Laravel e ordem
fixa. Reuniões e Tarefas terão detalhes JSON; o detalhe de Arquivo entregará
o próprio conteúdo como download. Todas as respostas respeitarão o escopo da
Chave, a disponibilidade dos módulos e as regras de visibilidade de Arquivos.
O fluxo de Solicitações e a entidade Sistema cliente serão retirados do
Gestão de Projetos. A documentação explicará que novas demandas são abertas
e avaliadas no Chamados.

## Histórias de usuário

1. Como administrador diretamente vinculado a um Projeto, quero gerenciar suas Chaves de API nas configurações, para habilitar integrações de leitura sem cadastrar uma entidade intermediária.
2. Como administrador do Projeto, quero emitir uma Chave de API com o próprio Projeto como proprietário, para limitar a credencial a ele.
3. Como administrador do Projeto, quero escolher a finalidade Integração ou IA, para descrever o uso da chave sem alterar suas permissões.
4. Como administrador do Projeto, quero escolher os papéis `viewer` ou `contributor`, para preservar o vocabulário disponível enquanto sua distinção futura é avaliada.
5. Como administrador do Projeto, quero renovar uma chave pelo gerenciador da biblioteca, para substituir uma credencial comprometida ou antiga.
6. Como administrador do Projeto, quero revogar uma chave pelo mesmo gerenciador, para interromper seu acesso imediatamente.
7. Como administrador do Projeto, quero que uma chave expirada não autentique, para respeitar a validade definida na emissão.
8. Como administrador do Projeto, quero que a exclusão lógica revogue as chaves ativas, para que um Projeto excluído não permaneça consultável.
9. Como administrador do Projeto, quero que a restauração não reative chaves revogadas, para poder decidir conscientemente quais novas credenciais emitir.
10. Como contribuidor, visualizador ou administrador sem vínculo direto com o Projeto, não quero poder administrar suas chaves, para preservar o controle local.
11. Como administrador do Projeto, quero um alerta ao mudar o slug quando houver chaves ativas, para saber que as URLs da API deixarão de funcionar.
12. Como administrador do Projeto, quero poder confirmar a alteração do slug sem fluxo extra de aprovação, para manter o formulário simples apesar da quebra explícita.
13. Como consumidor da API, quero autenticar exclusivamente com `Authorization: Bearer`, para não colocar segredos em URLs.
14. Como consumidor da API, quero que uma chave ausente, inválida, expirada ou revogada receba `401`, para diagnosticar problemas de autenticação.
15. Como consumidor da API, quero que `viewer` e `contributor` ofereçam as mesmas leituras nesta versão, para não depender de uma distinção ainda não confirmada.
16. Como consumidor da API, quero consultar um Projeto pelo slug, para obter seu contexto geral.
17. Como consumidor da API, quero receber nome, descrição integral, estado, tipo, fase, Projeto pai e tags, para interpretar o contexto sem consultas adicionais.
18. Como consumidor da API, quero receber a lista de módulos habilitados, para saber se Tarefas e Reuniões estão disponíveis.
19. Como consumidor da API, quero receber URL web e datas do Projeto, para orientar pessoas até a interface e reconhecer atualizações.
20. Como responsável pela privacidade, quero que a resposta do Projeto omita membros, subprojetos, recursos aninhados e regras internas de autorização, para evitar exposição desnecessária.
21. Como consumidor da API, quero listar Reuniões diretamente vinculadas ao Projeto, para descobrir seus encontros.
22. Como consumidor da API, quero encontrar Reuniões concluídas na listagem padrão, para consultar seu histórico.
23. Como consumidor da API, quero filtrar Reuniões por um ou mais estados, para selecionar uma etapa do seu ciclo.
24. Como consumidor da API, quero filtrar Reuniões por intervalo inclusivo da data agendada, para consultar um período.
25. Como consumidor da API, quero buscar Reuniões por título ou local sem depender de maiúsculas e minúsculas, para encontrar o encontro certo.
26. Como consumidor da API, quero uma listagem de Reuniões com metadados e sem Transcrições longas, para paginar a descoberta com respostas menores.
27. Como consumidor da API, quero consultar uma Reunião por identificador, para recuperar Anotações prévias, Ata e Transcrição integrais.
28. Como consumidor da API, quero receber a Pauta ordenada e as anotações de cada Item de pauta, para reconstruir o conteúdo discutido.
29. Como consumidor da API, quero receber comentários ativos com texto, data e nome do autor, para compreender o debate sem dados pessoais adicionais.
30. Como consumidor da API, quero ver resumos dos Projetos vinculados e das referências da Pauta, para entender uma Reunião compartilhada.
31. Como consumidor da API, quero ler a Reunião inteira quando ela estiver diretamente vinculada ao meu Projeto, mesmo que também cite outro Projeto, para não perder partes do registro.
32. Como responsável pelo isolamento entre Projetos, quero que referências externas da Reunião não liberem o detalhe dos recursos citados, para manter o escopo da chave.
33. Como responsável pelo isolamento entre Projetos, quero que a Chave de um subprojeto veja somente Reuniões diretamente vinculadas a ele, para não herdar automaticamente as Reuniões do Projeto pai.
34. Como consumidor da API, quero receber `409 meetings_module_disabled` quando o módulo de Reuniões estiver desligado, para distinguir indisponibilidade de falta de autorização.
35. Como consumidor da API, quero listar Tarefas não excluídas do Projeto, inclusive concluídas, para conhecer o trabalho existente.
36. Como consumidor da API, quero consultar uma Tarefa por identificador, para recuperar seu contexto integral.
37. Como consumidor da API, quero a descrição integral tanto na listagem quanto no detalhe, para não decidir com base em um resumo incompleto.
38. Como consumidor da API, quero filtrar Tarefas por um ou mais estados e prioridades, para selecionar trabalho relevante.
39. Como consumidor da API, quero filtrar Tarefas por intervalo inclusivo da data de vencimento, para encontrar prazos em um período.
40. Como consumidor da API, quero filtrar por qualquer uma das tags informadas por slug, para consultar categorias de trabalho.
41. Como consumidor da API, quero buscar Tarefas por título e descrição, para localizar assuntos específicos.
42. Como consumidor da API, quero receber estado e prioridade com valor técnico e rótulo, datas, tags, URL web e nomes dos responsáveis, para interpretar e apresentar a Tarefa.
43. Como responsável pela privacidade, quero que a API omita e-mail e identificadores pessoais dos responsáveis, para não expor dados desnecessários.
44. Como consumidor da API, quero receber `409 tasks_module_disabled` quando o módulo de Tarefas estiver desligado, para entender por que a leitura não está disponível.
45. Como consumidor da API, quero listar Arquivos acessíveis pelo Projeto, pelas suas Tarefas, pelas Reuniões diretamente vinculadas e pelos compartilhamentos explícitos com elas, para descobrir os conteúdos pertinentes.
46. Como consumidor da API, quero ver cada Arquivo apenas uma vez, mesmo quando houver mais de um caminho de acesso, para não processar duplicatas.
47. Como consumidor da API, quero filtrar Arquivos pelo nome exibido, tipo de Proprietário do arquivo, tipo MIME e intervalo de upload, para encontrar um conteúdo específico.
48. Como consumidor da API, quero receber UUID, nome exibido, extensão, tipo MIME, tamanho, data de upload, resumo do Proprietário do arquivo e URL de download, para escolher o que baixar.
49. Como responsável pela privacidade, quero que nome original, disco, caminho físico e Autor do arquivo não sejam expostos, para restringir metadados internos.
50. Como consumidor da API, quero baixar o Arquivo por UUID com minha Chave de API, para usar o conteúdo original em uma integração.
51. Como responsável pela segurança, quero que o download force `attachment` e `nosniff`, para evitar renderização de conteúdo ativo no domínio da aplicação.
52. Como consumidor da API, quero que Arquivos de módulos desativados desapareçam se não houver outro caminho de acesso ativo, para não contornar o estado dos módulos.
53. Como consumidor da API, quero continuar acessando Arquivos diretamente pertencentes ao Projeto mesmo quando Tarefas ou Reuniões estiverem desativadas, para não perder conteúdo próprio do Projeto.
54. Como responsável pelo isolamento entre Projetos, quero que Arquivos de Projeto pai, subprojetos ou Reuniões sem vínculo direto não sejam incluídos apenas por essas relações, para preservar o escopo explícito.
55. Como consumidor da API, quero `page` e `per_page` nas três coleções com `data`, `links` e `meta`, para navegar resultados sem um protocolo especial.
56. Como consumidor da API, quero 20 itens por página por padrão e no máximo 100, para controlar o tamanho das respostas.
57. Como consumidor da API, quero uma ordem fixa com identificador como desempate, para percorrer cada coleção de maneira previsível.
58. Como consumidor da API, quero `422` para filtros, valores, intervalos ou paginação inválidos, para corrigir minha consulta.
59. Como responsável pelo isolamento entre Projetos, quero `404` para slug, identificador ou UUID fora do escopo, para não revelar a existência de recursos alheios.
60. Como consumidor da API, quero que recursos excluídos logicamente não apareçam em listagens nem detalhes, para consultar apenas o estado ativo.
61. Como consumidor da API, quero `403` quando uma chave válida não possuir a ability requerida, para distinguir autorização de autenticação.
62. Como consumidor da API, quero receber `429` ao exceder 60 requisições por minuto por IP, para saber quando reduzir o ritmo das consultas.
63. Como equipe do Projeto, quero que a API de negócio ofereça somente leituras, para que integrações não criem nem alterem trabalho.
64. Como equipe do Projeto, quero que Solicitações e sua avaliação fiquem no Chamados, para não manter dois fluxos concorrentes de entrada e triagem.
65. Como desenvolvedor de integração, quero um guia com rotas, filtros, respostas, erros e exemplos de autenticação, para construir consumidores sem examinar a implementação.
66. Como desenvolvedor de integração, quero que o guia explique a quebra após alterar o slug, para atualizar URLs antes de uma mudança de nome do Projeto.

## Decisões de implementação

- A biblioteca `uspdev/api-keys` e o requisito PHP `^8.3` já estão presentes. O Projeto será seu owner direto sob o alias `project`, resolverá as abilities e usará o gerenciador da biblioteca. Não haverá tabela intermediária de Sistemas clientes.
- Somente administradores diretamente vinculados ao Projeto poderão emitir, renovar e revogar chaves. Contribuidores, visualizadores, administradores herdados e administradores globais não vinculados não poderão gerenciá-las. Expiração opcional, exibição única do segredo, renovação e revogação seguem o contrato público da biblioteca.
- `viewer` e `contributor` serão mantidos temporariamente com `projects.read`, `meetings.read`, `tasks.read` e `files.read`. Papéis desconhecidos não concedem abilities; não haverá wildcard. `integration` e `ai` serão finalidades descritivas, sem efeito na autorização.
- A API de negócio aceitará somente `GET` e credenciais no cabeçalho Bearer. Chaves em query string permanecerão desabilitadas. Operações web existentes e rotas administrativas de emissão, renovação e revogação da biblioteca não estão sujeitas à restrição de método da API de negócio.
- As rotas são `GET /api/projects/{project}`, `GET /api/projects/{project}/meetings`, `GET /api/projects/{project}/meetings/{meeting}`, `GET /api/projects/{project}/tasks`, `GET /api/projects/{project}/tasks/{task}`, `GET /api/projects/{project}/files` e `GET /api/projects/{project}/files/{uuid}`. `{project}` é o slug; `{meeting}` e `{task}` são IDs numéricos; Arquivos usam UUID.
- A ability e o pertencimento ao Projeto proprietário da chave serão verificados separadamente. Um recurso inexistente, excluído logicamente ou fora desse escopo responderá `404`; a API não oferecerá lixeira nem leitura de excluídos. Alterar o slug quebra as URLs, sem alias ou redirecionamento; um alerta visível aparecerá no formulário quando houver chave ativa, sem confirmação adicional.
- A exclusão lógica do Projeto revogará imediatamente suas chaves ativas. Restauração não reativará credenciais antigas. A leitura do Projeto retornará ID, slug, nome, descrição integral, estado com valor e rótulo, resumos de tipo, fase e Projeto pai, tags, `modules.enabled`, URL web e timestamps. Não haverá `modules.tasks_enabled`, membros nem coleções embutidas.
- Reuniões exigem vínculo direto com o Projeto da URL, inclusive quando ele é subprojeto. Uma Reunião compartilhada entre Projetos será devolvida integralmente, com Pauta e referências externas resumidas, sem conceder acesso aos detalhes dos recursos externos. A lista retorna ID, título, estado, data agendada, local, Projetos vinculados, timestamps e URL web; o detalhe acrescenta Anotações prévias, Ata, Transcrição, Pauta ordenada e comentários ativos cronológicos com nome do autor. Arquivos são recurso separado.
- A lista de Reuniões aceitará `status[]`, `scheduled_from`, `scheduled_to` e `search` em título/local, sem diferenciar maiúsculas e minúsculas. Datas são inclusivas em ISO 8601; a ordem fixa é data agendada e ID decrescentes. Incluirá Reuniões concluídas. Com módulo desativado, lista e detalhe responderão `409 meetings_module_disabled`.
- Tarefas manterão a representação atual, incluindo descrição integral em lista e detalhe, estado e prioridade com valor e rótulo, datas, tags, URL web e responsáveis somente pelo nome. A lista aceitará `status[]`, `priority[]`, `due_from`, `due_to`, `tag[]` por slug com correspondência a qualquer tag e `search` em título/descrição. Datas de vencimento são inclusivas em `YYYY-MM-DD`; a ordem fixa é atualização e ID decrescentes; Tarefas concluídas continuam incluídas. Com módulo desativado, lista e detalhe responderão `409 tasks_module_disabled`.
- A coleção de Arquivos reúne os de propriedade direta do Projeto, os de suas Tarefas, os das Reuniões diretamente vinculadas e os explicitamente compartilhados com essas Reuniões. A mesma entrada aparece uma vez. Vínculos apenas com Projeto pai, subprojeto ou Reunião indireta não bastam. Caminhos por módulo desativado ou recurso excluído não autorizam leitura; um caminho alternativo ativo continua válido.
- Cada item de Arquivo retorna UUID, nome exibido, extensão, tipo MIME, tamanho em bytes, data de upload, resumo do Proprietário do arquivo e URL do download. O proprietário Projeto inclui ID/slug/nome; Tarefa e Reunião incluem ID/título. Não são retornados nome original, Autor do arquivo, caminho, disco, miniatura ou indicadores do caminho de acesso. A coleção aceita `search` no nome exibido, `owner_type[]` (`project`, `task`, `meeting`), `mime_type[]` exato e `uploaded_from`/`uploaded_to` inclusivos em ISO 8601; a ordem fixa é upload e identificador decrescentes.
- `GET /files/{uuid}` entrega diretamente o conteúdo original, sem JSON de detalhe ou endpoint de miniatura, com `Content-Type`, `Content-Length`, nome seguro em `Content-Disposition: attachment` e `X-Content-Type-Options: nosniff`. Lista e download compartilham a mesma regra de visibilidade.
- As três coleções usam `page` e `per_page` (padrão 20, intervalo 1–100), envelope Laravel `data`/`links`/`meta`, ordenação fixa e desempate por identificador. Não haverá `sort`, `direction` nem opção sem paginação. Erros usam `401` para autenticação, `403` para ability, `404` para inexistência ou escopo, `409` com código técnico para módulo desativado e `422` no formato Laravel para consulta inválida. O grupo `/api` mantém 60 requisições por minuto por IP e responde `429` ao exceder; downloads também contam.
- O fluxo de Solicitações será retirado das rotas de negócio e web, interface, modelos, serviços e persistência. A criação manual de Tarefas continua disponível na interface existente. Como nada dessa funcionalidade foi implantado fora de desenvolvimento, as estruturas específicas dela e de Sistemas clientes podem ser removidas diretamente, sem migração de dados legados.
- O guia público e seu teste de contrato serão reescritos para a API somente de leitura e justificarão que a entrada e a triagem de demandas pertencem ao Chamados. Não será criado Swagger ou OpenAPI.

## Decisões de teste

- O ponto de teste principal é a interface HTTP da aplicação, com Chaves de API reais emitidas por meio do contrato público da biblioteca. Os testes devem verificar respostas, efeitos e permissões observáveis, não detalhes de controllers, consultas ou implementação interna do package.
- Testes de integração cobrirão emissão, renovação, revogação, expiração, ownership direto, papéis e administração web local; chave inválida, ausência de Bearer e tentativa de envio por query string; exclusão e restauração do Projeto; aviso de quebra do slug.
- Cada recurso terá testes HTTP de listagem e detalhe (ou download), representação, filtros válidos e inválidos, ordenação, paginação, módulos desativados, exclusão lógica e isolamento entre Projetos. Reuniões cobrirão vínculos múltiplos e referências externas; Arquivos cobrirão propriedade, compartilhamento, deduplicação, caminho alternativo ativo e cabeçalhos de download com armazenamento de teste.
- A retirada de Solicitações e Sistemas clientes será verificada pela ausência de suas rotas e interface e pela continuidade da criação manual de Tarefas. Os testes de documentação verificarão o novo contrato e a justificativa do Chamados, sem conservar expectativas do fluxo descartado.
- O código já possui testes de integração para Projeto, Tarefas, gerenciamento de Chaves de API e exposição de Arquivos; esses são o precedente para ampliar e adaptar a suíte. Testes unitários específicos para cada filtro ou controller não são um novo ponto de teste necessário.

## Fora do escopo

- Criação, alteração ou exclusão de Projetos, Reuniões, Tarefas ou Arquivos pela API de negócio; criação ou avaliação de Solicitações no Gestão de Projetos; integração de escrita com o Chamados.
- Identidade de usuários individuais dos sistemas consumidores, referências por solicitante, callbacks, webhooks ou uma biblioteca cliente/plugin para outros sistemas.
- Endpoint de Links externos, mistura de Links com Arquivos, miniaturas na API, detalhe JSON separado de Arquivo e indicador de como cada Arquivo ficou acessível.
- Swagger/OpenAPI, versionamento inicial das rotas, ordenação configurável, filtro de Tarefas por responsável, quotas por Chave ou por bytes e limite específico para download.
- Compatibilidade com o slug antigo, reativação automática de chaves após restauração e redesenho definitivo da distinção entre `viewer` e `contributor` antes de confirmação com a supervisão.

## Observações adicionais

- A integração existente e os artefatos de planejamento sobre Sistemas clientes e Solicitações representam apenas o estado de desenvolvimento descartado; os ADRs vigentes e esta especificação definem o alvo da implementação.
- A biblioteca fornece infraestrutura de credenciais, mas o Gestão de Projetos continua responsável por declarar abilities nas rotas e validar explicitamente o escopo do Projeto.
- A especificação e os tickets não autorizam implantação em produção; a compatibilidade de ambiente deve ser verificada separadamente.
