# Menções polimórficas entre textos e entidades

Status: ready-for-agent

## Declaração do problema

O sistema representa Menções apenas entre um campo Markdown e um usuário. A origem já é polimórfica, mas o destino permanece preso a `mentioned_user_id`, o que impede tratar Projeto, Tarefa, Reunião e Arquivo como destinos estruturados e consultáveis.

Autores conseguem mencionar usuários por autocomplete, mas ainda precisam recorrer a links comuns ou conhecer rotas e identificadores para apontar para outras entidades. Isso é especialmente difícil para Tarefas e Reuniões, cujos IDs não são informação editorial natural. Arquivos possuem um seletor próprio, porém suas referências atuais não participam do índice de Menções.

A nomenclatura atual também produz uma separação artificial entre Menção e Referência interna. O domínio passou a definir Menção como qualquer ligação estruturada presente em um texto que aponte para uma Entidade mencionável. Menção a usuário continua tendo a intenção específica de chamar a atenção de uma pessoa, mas essa intenção não deve limitar a infraestrutura.

O índice atual ainda registra `created_by` e timestamps que não podem ser reconstruídos fielmente a partir do Markdown. Eles confundem o criador da linha derivada, o autor original da entidade e o editor que inseriu a Menção. Também não existem caminhos simétricos e claramente nomeados para consultar os destinos de uma fonte e as fontes que apontam para um destino.

## Solução

Generalizar Menções para uma relação polimórfica entre uma fonte textual registrada explicitamente e uma Entidade mencionável também registrada explicitamente. Usuário, Projeto, Tarefa, Reunião e Arquivo serão os destinos iniciais; descrições de Projeto e Tarefa, Anotações prévias de Reunião e Item de pauta e texto de Comentário ativo serão as fontes iniciais.

Toda Menção usará a sintaxe explícita `@[Rótulo histórico](mention:tipo:chave)`. O editor oferecerá um autocomplete unificado iniciado por `@`, agrupado e filtrável por tipo, sem exigir que o autor conheça identificadores. Links Markdown comuns continuarão sendo apenas links.

O Markdown permanecerá como fonte editorial da verdade. A tabela `mentions` será um índice derivado, deduplicado e reconstruível, com nomes explícitos para origem, campo textual e destino. O índice permitirá consultar Menções de saída e de entrada, mas não armazenará autoria, cronologia ou posições das ocorrências.

Um módulo concreto `MentionManager` concentrará pesquisa, validação, sincronização, apresentação e reconstrução. As diferenças reais entre os cinco destinos ficarão escondidas em adaptadores internos. A aplicação continuará consumindo a funcionalidade somente por suas rotas web e por seu comando operacional.

## Histórias de usuário

1. Como autor, quero mencionar um Usuário, Projeto, Tarefa, Reunião ou Arquivo a partir do mesmo editor, para criar ligações estruturadas sem aprender fluxos diferentes.
2. Como autor, quero abrir o autocomplete digitando `@`, para iniciar uma Menção de maneira conhecida e rápida.
3. Como autor, quero pesquisar destinos por nome, título ou rótulo, para não precisar conhecer IDs, UUIDs ou slugs.
4. Como autor, quero visualizar os resultados agrupados por tipo, para distinguir entidades com nomes semelhantes durante a seleção.
5. Como autor, quero filtrar resultados por Pessoas, Projetos, Tarefas, Reuniões ou Arquivos, para reduzir ruído em buscas amplas.
6. Como autor, quero que os resultados do contexto atual apareçam primeiro, para encontrar rapidamente as entidades mais prováveis.
7. Como autor de uma descrição de Projeto, quero encontrar primeiro seus membros diretos, Tarefas, Reuniões vinculadas e Arquivos permitidos, para permanecer no contexto do Projeto.
8. Como autor de uma descrição de Tarefa, quero encontrar primeiro membros e entidades de seu Projeto e os Arquivos permitidos da Tarefa, para mencionar itens relacionados.
9. Como autor de Anotações prévias de Reunião ou Item de pauta, quero encontrar primeiro membros e Projetos vinculados, Tarefas da pauta e Arquivos da Reunião, para preparar a conversa com referências relevantes.
10. Como autor de Comentário, quero que o autocomplete herde o contexto do objeto comentado, para receber resultados coerentes com a conversa.
11. Como autor, quero pesquisar outros Projetos, Tarefas e Reuniões que eu possa visualizar após os resultados contextuais, para criar ligações legítimas entre contextos.
12. Como autor, quero que Usuários e Arquivos permaneçam limitados às regras contextuais próprias, para não ampliar a descoberta desses destinos.
13. Como autor, quero que a escolha no autocomplete insira uma sintaxe canônica, para que a Menção possa ser validada e reconstruída.
14. Como autor, quero que o seletor de Arquivos também insira a sintaxe canônica de Menção, para que Arquivos participem do mesmo índice.
15. Como colaborador de Reunião, quero mencionar diretamente um Arquivo já acessível à Reunião, para incluí-lo no texto sem passos extras.
16. Como colaborador de Reunião, quero ver a ação **Compartilhar com a reunião e mencionar** para um Arquivo elegível ainda não compartilhado, para conceder acesso explicitamente antes da inserção.
17. Como colaborador de Reunião, quero que compartilhar e mencionar permaneçam ações conceitualmente distintas, para que editar o texto não conceda ou revogue acesso implicitamente.
18. Como autor, quero que uma sintaxe de Menção malformada impeça o salvamento, para não acreditar que criei uma ligação válida quando não criei.
19. Como autor, quero receber uma mensagem única quando um destino novo não existir ou não for permitido, para que o formulário não revele entidades inacessíveis.
20. Como autor, quero que uma edição não relacionada preserve Menções históricas que perderam elegibilidade, para não ser obrigado a apagar conteúdo antigo.
21. Como autor, quero que remover uma Menção histórica e tentar adicioná-la novamente aplique as regras atuais, para que a exceção histórica não autorize novas inserções.
22. Como autor, quero que Menções repetidas ao mesmo destino no mesmo campo permaneçam no Markdown, para manter a redação original.
23. Como equipe de manutenção, quero que repetições no mesmo campo produzam uma única relação no índice, para evitar registros redundantes.
24. Como leitor, quero que toda Menção disponível apareça como `@{rótulo atual}`, para ter uma apresentação visual uniforme.
25. Como leitor, quero abrir o destino atual ao clicar em uma Menção autorizada, para navegar pelo sistema.
26. Como leitor, quero que o tipo da entidade esteja disponível em tooltip no hover e no foco, para distinguir Menções visualmente iguais.
27. Como usuário de tecnologia assistiva, quero que o nome acessível inclua tipo e rótulo atuais, para compreender o destino antes de navegar.
28. Como leitor, quero ver `Menção a {tipo}: você não tem permissão para visualizar` quando o destino existir, mas eu não tiver acesso, para entender claramente a restrição.
29. Como leitor, quero ver `Menção a {tipo}: destino não encontrado` quando o destino estiver excluído ou inexistente, para distinguir indisponibilidade de falta de permissão.
30. Como leitor sem acesso, quero que a renderização não revele o Rótulo histórico da Menção, para não expor nomes de entidades inacessíveis.
31. Como leitor, quero que uma Menção histórica volte a apresentar rótulo e link se meu acesso for restaurado, para recuperar a navegação sem reescrever o texto.
32. Como leitor de uma Menção a Reunião, quero que o link use um Projeto vinculado que eu possa visualizar, para abrir a rota contextual correta.
33. Como leitor sem Projeto acessível para uma Reunião, quero ver a mensagem de falta de permissão, para não receber um link inválido.
34. Como leitor, quero que uma Menção nunca conceda acesso ao destino, para preservar as políticas de autorização existentes.
35. Como leitor de uma Menção a Arquivo, quero que o acesso continue dependendo do Proprietário ou de Compartilhamento de arquivo com reunião, para manter a fronteira de segurança.
36. Como equipe de manutenção, quero consultar os destinos mencionados por uma fonte, para implementar navegação e funcionalidades futuras com nomes claros.
37. Como equipe de manutenção, quero consultar as fontes que mencionam um destino, para oferecer backlinks futuros sem varrer todos os textos.
38. Como equipe de manutenção, quero que consultas de entrada e saída apliquem autorização, para que o índice não se torne um caminho de vazamento.
39. Como equipe de manutenção, quero que o índice seja inteiramente reconstruível do Markdown, para recuperar divergências operacionais.
40. Como equipe de manutenção, quero que texto e índice sejam salvos na mesma transação, para nunca confirmar apenas metade da alteração.
41. Como equipe de manutenção, quero que o comando de reconstrução seja idempotente, para poder executá-lo com segurança durante diagnóstico e recuperação.
42. Como equipe de manutenção, quero que uma fonte excluída logicamente ou um Comentário inativo desapareça do índice, para não produzir backlinks para conteúdo indisponível.
43. Como equipe de manutenção, quero que restaurar uma fonte ou reativar um Comentário reconstrua suas relações, para recuperar o índice automaticamente.
44. Como equipe de manutenção, quero preservar relações cujo destino sofreu exclusão lógica, para que restaurações recuperem as Menções sem varredura global.
45. Como equipe de manutenção, quero remover relações de entrada após exclusão definitiva do destino, para não acumular relações polimórficas órfãs.
46. Como leitor, quero que a sintaxe continue renderizando “destino não encontrado” mesmo quando não houver linha no índice, para que a apresentação dependa do Markdown.
47. Como autor, quero que renomear uma entidade não reescreva outros textos, para evitar alterações editoriais automáticas e concorrentes.
48. Como leitor, quero ver o rótulo atual mesmo que o Markdown preserve o rótulo histórico, para reconhecer a entidade hoje.
49. Como usuário que duplica um Projeto, Tarefa ou Reunião, quero que o Markdown e suas Menções sejam preservados na cópia, para manter o conteúdo selecionado.
50. Como usuário que duplica uma entidade, quero que as Menções sejam sincronizadas somente depois que o novo contexto estiver completo e antes do commit, para não observar uma cópia inconsistente.
51. Como usuário que duplica um Projeto sem membros, quero que Menções históricas a usuários sejam preservadas sem adicionar esses usuários ao novo Projeto, para não alterar sua composição.
52. Como usuário que duplica um Projeto com Tarefas ou Reuniões, quero que Menções continuem apontando para os destinos originais, para não ter o texto reescrito silenciosamente.
53. Como leitor de uma cópia, quero que cada destino original seja exibido ou bloqueado segundo minha autorização atual, para que a duplicação não conceda acesso.
54. Como autor de uma entidade, quero que ela não possa mencionar a si própria em seu próprio campo, para evitar relações circulares sem valor de navegação.
55. Como autor de Comentário, quero poder mencionar o objeto comentado, para apontar explicitamente para o contexto sem que isso seja tratado como autorreferência.
56. Como equipe de manutenção, quero aliases estáveis em vez de nomes de classes no Markdown e no banco, para permitir refatorações internas sem invalidar conteúdo.
57. Como equipe de manutenção, quero que Projeto use ID imutável em vez de slug na sintaxe, para que alterações de slug não quebrem Menções.
58. Como equipe de manutenção, quero que Arquivo use seu UUID público na sintaxe, para preservar sua identidade pública já estabelecida.
59. Como equipe de manutenção, quero que o índice não registre `created_by` nem timestamps enganosos, para não apresentar autoria ou cronologia que uma reconstrução não consegue preservar.
60. Como equipe de manutenção, quero que o campo Markdown de origem participe da identidade da relação, para distinguir o mesmo destino mencionado em campos diferentes.
61. Como equipe de manutenção, quero que links Markdown comuns permaneçam fora do índice, para não inferir semântica estruturada a partir de rotas mutáveis.
62. Como usuário, quero que links comuns de Arquivos já presentes continuem funcionando como links, para não exigir uma conversão de conteúdo desta branch.
63. Como equipe de manutenção, quero uma única implementação para pesquisa, sincronização, apresentação e reconstrução, para evitar divergência de regras entre consumidores internos.
64. Como equipe de manutenção, quero que diferenças entre tipos fiquem atrás de adaptadores internos, para adicionar novos destinos deliberadamente sem espalhar condicionais.
65. Como mantenedor, quero que a aplicação continue usando apenas rotas web e o comando existente, para não criar uma API pública desnecessária.

## Decisões de implementação

- **Linguagem de domínio:** Menção passa a ser qualquer ligação estruturada presente em um texto que aponte para uma Entidade mencionável. Menção a usuário mantém a intenção específica de chamar a atenção. Referência interna e Referência de arquivo deixam de ser termos canônicos; esta última passa a ser Menção a arquivo.
- **Registro explícito:** possuir um model ou campo Markdown não torna uma entidade automaticamente mencionável nem torna um campo automaticamente indexável. Origens, campos e destinos usam listas fechadas.
- **Destinos iniciais:** Usuário, Projeto, Tarefa, Reunião e Arquivo. Item de pauta e Comentário podem ser fontes, mas não são destinos nesta entrega.
- **Fontes iniciais:** descrição de Projeto, descrição de Tarefa, Anotações prévias de Reunião, Anotações prévias de Item de pauta e texto de Comentário ativo. Ata, Transcrição e descrição de Tipo de projeto não originam Menções.
- **Sintaxe canônica:** somente `@[Rótulo histórico](mention:tipo:chave)` produz uma Menção. Links internos comuns não são inferidos como Menções. Construções com formato de Menção, mas tipo ou chave inválidos, impedem o salvamento; conteúdo equivalente em blocos ou código inline continua sendo código, não Menção.
- **Aliases de destino:** `user`, `project`, `task`, `meeting` e `file`; `file` representa o model técnico de mídia sem levar esse termo para o domínio.
- **Aliases de origem:** `project`, `task`, `meeting`, `meeting_item` e `comment`.
- **Identidades na sintaxe:** Usuário, Projeto, Tarefa e Reunião usam ID numérico imutável. Arquivo usa UUID público. Slug, rótulo e rota não identificam a Menção.
- **Rótulo histórico:** a sintaxe preserva o rótulo do momento da inserção. Renomeações não reescrevem Markdown. Renderização, tooltip e nome acessível resolvem o rótulo atual.
- **Tabela derivada:** `mentions` contém `source_type`, `source_id`, `source_field`, `target_type` e `target_id`. As colunas são obrigatórias para relações resolvidas.
- **Unicidade:** existe uma relação por tipo e ID da fonte, campo de origem, tipo e ID do destino. Repetições no Markdown não criam linhas adicionais.
- **Índices:** a estrutura favorece sincronização e limpeza por fonte/campo e consultas de entrada por tipo e ID do destino.
- **Sem autoria ou cronologia:** a tabela não possui `created_by`, `created_at` ou `updated_at`. Autoria e histórico pertencem aos models de origem e à auditoria existente.
- **Relacionamentos:** o model de Menção expõe `source()` e `target()`. Fontes expõem `outgoingMentions()` e destinos, `incomingMentions()`. Os nomes antigos baseados em `mentionable` são removidos.
- **Morph map:** aliases estáveis são persistidos no banco; nomes completos de classes não são armazenados. A aplicação valida separadamente quais aliases podem ser origem e quais podem ser destino.
- **Módulo central:** um `MentionManager` concreto concentra `search`, `synchronize`, `present` e `rebuild`. Ele usa Eloquent, policies e transações diretamente, sem contrato PHP abstrato, repositório genérico, porta HTTP extra ou API paralela.
- **Adaptadores internos:** cada tipo de destino encapsula formato e resolução de chave, pesquisa, autorização, rótulo e construção de rota. Controllers, renderizador e comando não fazem ramificações próprias por tipo.
- **Parser compartilhado:** reconhecimento, validação, indexação e apresentação usam a mesma interpretação da AST Markdown. Não há reescrita do HTML final por expressão regular.
- **Autocomplete:** `@` abre um seletor unificado, inicialmente em **Usuários**, com filtros para Usuários, Projetos, Tarefas, Reuniões e Arquivos. Cada filtro mostra somente o nome ou título; as abas Tarefas e Reuniões ordenam “Em andamento” antes de “Concluídas”, separam esses estados por rótulos estáticos sem contador ou toggle e identificam cada opção por símbolo colorido e nome acessível. Todos os resultados são carregados de uma vez, enquanto a área de resultados mantém altura máxima com rolagem interna; a caixa mantém largura fixa e rótulos visíveis são truncados com reticências. Resultados nunca exigem identificadores do autor.
- **Prioridade contextual:** resultados do contexto natural vêm primeiro. Nas abas Projetos, Tarefas e Reuniões, sem termo, aparecem apenas resultados do contexto e uma orientação para digitar; com termo, a busca se amplia, respectivamente, para Projetos, Tarefas e Reuniões visíveis, separando resultados relacionados dos demais acessíveis. A aba de Tarefas continua separando estados em andamento e concluídos, e a aba de Reuniões usa o mesmo agrupamento e indicadores, mantendo reuniões concluídas elegíveis. Em fontes de Reunião, Item de pauta e Comentário associado a esses objetos, o contexto inicial de Tarefas contém somente Tarefas presentes na pauta; a busca com termo pode ser ampliada para outras Tarefas visíveis, mantendo as da pauta primeiro. Projeto, Tarefa e Reunião podem ampliar a busca para outros destinos visíveis; Usuários e Arquivos permanecem contextuais. Comentário herda seu objeto.
- **Elegibilidade de Usuário:** novas Menções a usuário mantêm a regra atual de participação direta no contexto. Acesso herdado não torna o usuário elegível; o próprio autor pode ser selecionado.
- **Elegibilidade de outras entidades:** Projeto, Tarefa e Reunião são elegíveis quando o autor pode visualizá-los pelas policies atuais. Arquivo mantém o seletor contextual e as regras de compartilhamento existentes.
- **Validação na criação e edição:** fontes novas validam todas as Menções. Edições validam apenas destinos adicionados em relação ao Markdown persistido, preservando relações históricas.
- **Erro indistinguível:** destino novo inexistente e destino não autorizado produzem `Uma ou mais Menções não existem ou não são permitidas neste contexto.`, evitando enumeração.
- **Autorreferência:** Projeto, Tarefa e Reunião não podem mencionar a si próprios em seus próprios campos. A opção não aparece na pesquisa e é rejeitada no salvamento. Comentário pode mencionar o objeto comentado.
- **Atomicidade:** validação, persistência do Markdown e sincronização do índice ocorrem na mesma transação. Falha em qualquer etapa reverte texto e relações.
- **Sincronização:** relações ausentes no novo texto são removidas, novas relações são criadas e relações inalteradas permanecem. O índice não armazena posições ou contagem de ocorrências.
- **Reconstrução:** o comando de reconstrução continua idempotente, cobre todas as fontes registradas e ignora sintaxes cujo destino não possa ser resolvido. Ele não tenta reconstruir autoria ou datas.
- **Exclusão lógica do destino:** relações de entrada permanecem no índice e resolvem como destino não encontrado até eventual restauração.
- **Exclusão definitiva do destino:** relações de entrada são removidas sem reescrever Markdown. O renderizador ainda reconhece a sintaxe e apresenta destino não encontrado.
- **Ciclo da fonte:** exclusão lógica da fonte ou inativação de Comentário remove relações de saída. Restauração ou reativação reconstrói a partir do Markdown. Exclusão definitiva também limpa as relações.
- **Apresentação disponível:** todos os tipos aparecem como `@{rótulo atual}` e funcionam como link.
- **Indicação auxiliar do tipo:** link disponível recebe nome acessível e tooltip no formato `{tipo}: {rótulo}`, exibido no hover e foco por teclado. Não há interação extra em dispositivos de toque.
- **Apresentação sem autorização:** exibe `Menção a {tipo}: você não tem permissão para visualizar`, sem link e sem Rótulo histórico.
- **Apresentação indisponível:** exibe `Menção a {tipo}: destino não encontrado`, sem link e sem Rótulo histórico.
- **Autorização por leitor:** cada renderização reavalia a policy. A linha do índice e a sintaxe nunca concedem acesso. Consultas em qualquer direção também precisam filtrar fontes e destinos autorizados.
- **Rota de Reunião:** a sintaxe guarda apenas o ID. A apresentação escolhe dinamicamente um Projeto vinculado acessível ao leitor pela regra contextual existente. Sem Projeto acessível, o estado é falta de permissão.
- **Menção a Arquivo:** o UUID identifica o destino, mas acesso continua dependendo do Proprietário do arquivo ou de Compartilhamento de arquivo com reunião.
- **Compartilhamento na Reunião:** Arquivo já acessível é mencionado diretamente. Arquivo elegível ainda não compartilhado oferece **Compartilhar com a reunião e mencionar**, persistindo o compartilhamento antes de inserir a sintaxe. Remover a Menção não revoga o compartilhamento.
- **Duplicação:** a cópia preserva o Markdown e as chaves originais. O índice só é sincronizado depois de membros, Projetos e demais vínculos da cópia estarem completos, ainda antes do commit.
- **Menções copiadas:** são tratadas como relações históricas e não adicionam membros, não compartilham Arquivos e não concedem acesso. Cada leitor recebe link ou mensagem conforme sua policy.
- **Destinos de cópias:** Tarefas e Reuniões mencionadas continuam apontando para entidades originais mesmo quando essas entidades também forem duplicadas. Não há remapeamento original → cópia.
- **Migração não publicada:** a estrutura atual nasceu apenas em `release/2.1`, que não está em produção. A migração original de criação de `mentions` é reescrita diretamente; não há estratégia ampliar primeiro, remover depois.
- **Bancos de desenvolvimento:** ambientes que já executaram a migração da branch devem recriar ou reverter a estrutura durante o desenvolvimento.
- **Links antigos de Arquivo:** não há conversor. Links comuns existentes continuam como links e não entram no índice; novas inserções usam a sintaxe de Menção.
- **Canal de consumo:** a aplicação usa somente rotas web autenticadas e o comando operacional. Nenhuma API pública é criada.
- **Backlinks internos:** o índice e o módulo permitem consultas autorizadas nas duas direções, mas não há nova interface visual nesta entrega.
- **Notificações:** nenhuma Menção gera notificação, e-mail ou evento de acompanhamento. Uma funcionalidade futura deve reagir especificamente a Menção a usuário.

## Decisões de teste

- Testes devem afirmar comportamento observável por respostas HTTP, HTML renderizado, mensagens de validação, autorização e estado persistido. Não devem conhecer a organização dos adaptadores internos nem afirmar sequências privadas de chamadas.
- O ponto de teste principal são as rotas web autenticadas, cobrindo autocomplete, filtros, prioridade contextual, salvamento, atomicidade, mensagens indistinguíveis, apresentação, compartilhamento de Arquivo e duplicação.
- O `MentionManager` é um ponto de teste de integração com banco para comportamentos combinatórios difíceis de exercitar economicamente por HTTP: matriz de origens e destinos, parsing, deduplicação, sincronização, consultas nas duas direções e ciclos de exclusão/restauração.
- Os adaptadores de destino não recebem suítes próprias quando seu comportamento já estiver coberto pelo ponto do módulo. O objetivo é permitir refatoração interna sem reescrever testes.
- O comando de reconstrução é exercitado pelo ponto Artisan, verificando idempotência, fontes registradas, destinos irresolvíveis, relações de destinos excluídos logicamente e recuperação de fontes restauradas.
- Dusk fica limitado a comportamento dependente do navegador: abertura do seletor por `@`, mudança de filtro, agrupamento visual, navegação por teclado e tooltip no hover/foco.
- Testes de funcionalidade existentes de Menções e de Referências de arquivo/Compartilhamentos são o prior art para autorização, transações e persistência.
- Testes unitários existentes do extrator e renderizador são o prior art para AST, blocos de código, links comuns, sintaxes malformadas e HTML seguro; casos que atravessam autorização ou banco devem subir para o ponto do módulo ou web.
- Testes de navegador existentes do editor Markdown são o prior art para interação real com EasyMDE e comportamento quando dependências globais não estiverem disponíveis.
- Cobrir os cinco destinos com rótulo atual, chave correta, link autorizado, tooltip/nome acessível, falta de permissão, exclusão lógica quando aplicável e inexistência.
- Cobrir IDs numéricos inválidos, UUID inválido, tipo desconhecido, destino inexistente, destino não autorizado e sintaxe semelhante dentro de código inline ou bloco.
- Cobrir link interno comum para uma entidade e comprovar que ele não é indexado como Menção.
- Cobrir ocorrências repetidas no mesmo campo e o mesmo destino em campos ou fontes diferentes.
- Cobrir criação com validação total, edição com validação apenas de adições, preservação histórica, remoção e tentativa posterior de reinserção.
- Cobrir rollback do Markdown quando validação ou sincronização falhar e rollback do índice quando o salvamento da fonte falhar.
- Cobrir limpeza de fonte excluída ou Comentário inativo, reconstrução na restauração/reativação, retenção do destino excluído logicamente e limpeza após exclusão definitiva.
- Cobrir autorização das consultas `outgoingMentions` e `incomingMentions`, garantindo que uma relação existente não revele uma fonte ou destino inacessível.
- Cobrir Reunião multiprojeto, escolha de Projeto acessível para a rota e falta de Projeto contextual autorizado.
- Cobrir Arquivo próprio, compartilhado, compartilhável e não elegível; comprovar que mencionar não compartilha e que a ação explícita compartilha antes de inserir.
- Cobrir autorreferência rejeitada para Projeto, Tarefa e Reunião e Menção permitida do Comentário ao objeto comentado.
- Cobrir duplicação com e sem membros, com destinos acessíveis e restritos e com Tarefas/Reuniões também copiadas, comprovando que as chaves continuam apontando para os originais.
- Cobrir reconstrução depois de renomear um destino e comprovar que o Markdown mantém o Rótulo histórico enquanto a exibição usa o atual.
- Cobrir a migração reescrita em banco vazio e os índices/garantias de unicidade suportados pelos bancos usados na suíte.
- Não criar testes para notificações, tela de backlinks, conversão de links antigos ou reescrita de rótulos, pois essas funcionalidades estão fora do escopo.

## Fora do escopo

- Notificações, e-mails, caixa de entrada ou eventos de acompanhamento decorrentes de Menções.
- Tela de backlinks, seção “Mencionado em”, agrupamento, paginação ou âncoras para ocorrências.
- Consulta confiável de “tudo que um usuário menciona” ou histórico de quem inseriu uma Menção.
- `created_by`, timestamps, posição, contagem ou identidade individual de cada ocorrência no índice.
- Reescrita do Markdown quando Usuário, Projeto, Tarefa, Reunião ou Arquivo for renomeado.
- Conversão de links Markdown comuns existentes para a nova sintaxe.
- Inferência de Menção a partir de rotas internas ou links comuns.
- Remapeamento de Menções para Tarefas ou Reuniões duplicadas.
- Item de pauta e Comentário como destinos mencionáveis.
- Ata, Transcrição e descrição de Tipo de projeto como fontes de Menções.
- Qualquer model não registrado explicitamente como origem ou destino.
- Menção autorreferente de Projeto, Tarefa ou Reunião em seu próprio campo.
- Compartilhamento implícito de Arquivo pela presença de uma Menção.
- Concessão de acesso, associação de membros ou alteração de entidades motivada por Menções.
- API pública, endpoints de API, contrato PHP abstrato, repositório genérico ou integração externa.
- Interação adicional para revelar o tipo da Menção em dispositivos de toque.
- Estratégia de compatibilidade com uma versão publicada da tabela atual.
- Protótipo de interface; as decisões editoriais foram resolvidas em conversa.

## Observações adicionais

- O ADR 0005 e o glossário de domínio são as autoridades para a nova definição de Menção.
- O ADR 0005 substitui a exclusividade de usuários do ADR 0002 e a terminologia/sintaxe de Referência de arquivo do ADR 0003. As demais decisões de segurança, propriedade e Compartilhamento de arquivo com reunião permanecem válidas.
- Esta especificação substitui apenas as seções de Menções e Referências de arquivo da especificação anterior de Markdown, Arquivos e Menções. A implementação já existente de renderização segura, editor, persistência de Arquivos e compartilhamentos continua sendo a base.
- O histórico das branches confirma que a implementação atual de Menções existe somente em `release/2.1`; não há dados de produção a preservar.
- Nenhum protótipo foi necessário. As decisões foram resolvidas pela entrevista de domínio e pela inspeção do código existente.
- A especificação está pronta para ser decomposta em tickets com dependências de bloqueio explícitas.
