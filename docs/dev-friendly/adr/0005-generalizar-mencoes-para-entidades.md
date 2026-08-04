# Generalizar Menções para entidades do sistema

**Status:** aceito

**Revisão posterior da interface:** a decisão histórica abaixo previa uma visão
inicial **Todos** e filtros para Pessoas. A interface vigente não exibe a aba
geral, usa **Usuários** como primeiro filtro e abre nessa aba. Cada filtro exibe
somente o nome ou título do destino, sem cabeçalho, prefixo visual ou tooltip
repetido; o nome acessível continua incluindo tipo e nome para distinguir opções
iguais. Esta revisão substitui apenas a apresentação do seletor; a indicação de
tipo nas Menções já renderizadas permanece conforme as decisões de acessibilidade
registradas neste ADR.

Menção passa a significar qualquer ligação estruturada presente em um texto que aponte para uma entidade identificável do sistema, e não apenas a ligação destinada a chamar a atenção de um usuário. A infraestrutura de Menções deverá admitir origem e destino polimórficos, preservando regras específicas por tipo de destino, como a intenção de chamar a atenção própria da Menção a usuário.

Esta decisão substitui a exclusividade de usuários estabelecida no ADR 0002. A generalização oferece um único conceito para navegação e consultas de ida e volta entre textos e entidades; em contrapartida, consumidores não poderão mais inferir somente pelo termo “Menção” que haverá uma pessoa a ser notificada.

Somente tipos registrados explicitamente como Entidades mencionáveis poderão ser destinos. Cada tipo deverá possuir identidade estável e regras próprias de descoberta e visualização; a existência de um model ou registro no banco não o tornará mencionável automaticamente. Essa lista fechada impede que a sintaxe ou o relacionamento polimórfico exponha tipos internos não previstos.

A lista inicial de Entidades mencionáveis será formada por Usuário, Projeto, Tarefa, Reunião e Arquivo. Item de pauta e Comentário poderão ser fontes textuais, mas não serão destinos enquanto não possuírem identidade navegável independente e regras adequadas para estados indisponíveis. A antiga Referência de arquivo passa a ser denominada Menção a arquivo.

Somente a sintaxe explícita `@[Rótulo histórico](mention:tipo:chave)` produzirá uma Menção estruturada. Links Markdown comuns, ainda que apontem para uma rota interna, não serão inferidos como Menções. A forma explícita desacopla a identidade da entidade do formato de suas rotas e permite reconstruir o índice diretamente do Markdown.

O índice derivado representará somente os caminhos reconstruíveis da fonte textual para os destinos mencionados e de cada destino para suas fontes. O campo `created_by` não integrará esse modelo porque a identidade de quem inseriu uma Menção não está codificada no Markdown e não pode ser restaurada fielmente: o autor original da entidade, seu editor atual e quem criou a linha do índice são papéis distintos. Consultar “tudo que um usuário menciona” fica fora desse índice e exigiria, se necessário, um histórico editorial próprio.

Ocorrências repetidas do mesmo destino em um único campo produzirão uma só relação no índice, com unicidade por fonte, campo e destino. O Markdown continuará preservando todas as ocorrências; contagem e posição serão calculadas a partir dele quando necessárias, sem armazenar coordenadas frágeis ou transformar o índice em uma segunda fonte editorial.

As fontes iniciais serão `Project.description`, `Task.description`, `Meeting.notes`, `MeetingItem.notes` e `Comment.text`, este último somente enquanto o Comentário estiver ativo. Tipos e campos de origem também dependerão de registro explícito; a simples existência de uma coluna textual ou de suporte a Markdown não a incluirá no índice. Ata e Transcrição permanecem fora por serem textos simples.

As chaves persistidas na sintaxe serão os IDs numéricos imutáveis de Usuário, Projeto, Tarefa e Reunião e o UUID público já existente de Arquivo. Em particular, o slug mutável de Projeto não será sua identidade de Menção. Durante a indexação, a chave textual será resolvida para a chave interna usada pelo relacionamento polimórfico; rótulos e rotas atuais serão resolvidos apenas para exibição e navegação.

A elegibilidade será específica por tipo. Menções a usuário manterão as regras atuais de participação direta no contexto; Projeto, Tarefa e Reunião serão elegíveis quando o autor puder visualizá-los segundo suas políticas; Arquivo preservará as regras contextuais do seletor existente, inclusive a exigência de compartilhamento com reunião quando aplicável. A inserção de uma Menção não concederá acesso, e a autorização do destino será reavaliada para cada leitor durante a renderização.

Mudanças posteriores de permissão não removerão Menções históricas do Markdown nem do índice e não bloquearão edições não relacionadas. A renderização distinguirá explicitamente um destino existente que o leitor não tem autorização para visualizar de um destino excluído ou inexistente, sempre sem link e sem expor o rótulo histórico. Se o acesso for restaurado, a resolução normal do rótulo e do link voltará a funcionar. Consultas em qualquer direção deverão aplicar autorização; a existência da relação indexada nunca concederá acesso.

Quando faltar autorização, o texto visível seguirá o padrão `Menção a {tipo}: você não tem permissão para visualizar`. Para destino excluído ou inexistente, seguirá `Menção a {tipo}: destino não encontrado`. A explicação será apresentada diretamente no conteúdo e não dependerá apenas de tooltip.

Relações cujo destino sofreu exclusão lógica permanecerão no índice para voltar a funcionar em uma eventual restauração. A exclusão definitiva removerá as relações de entrada, mas não reescreverá o Markdown. Reconstruções ignorarão sintaxes cujo destino não possa ser resolvido; ainda assim, o renderizador reconhecerá a sintaxe no próprio Markdown e apresentará “destino não encontrado”. Desse modo, o índice não acumulará referências polimórficas órfãs.

Cada relação identificará também `source_field`, o nome técnico do atributo Markdown que contém a Menção, como `description`, `notes` ou `text`. O campo participará da unicidade para distinguir Menções ao mesmo destino feitas em diferentes campos de uma única entidade e para permitir sincronização e navegação até a origem correta.

A tabela `mentions` nomeará suas pontas como `source_type`/`source_id` e `target_type`/`target_id`. O model oferecerá `source()` e `target()`; fontes oferecerão `outgoingMentions()` e destinos, `incomingMentions()`. Os nomes legados `mentionable_type`, `mentionable_id` e `mentions()` serão removidos porque descrevem de forma ambígua a origem como “mencionável”. A unicidade abrangerá tipo e ID da fonte, `source_field` e tipo e ID do destino.

Como a estrutura original de Menções foi introduzida somente na branch ainda não publicada `release/2.1`, a migração `2026_07_23_090000_create_mentions_table.php` será reescrita diretamente com o formato definitivo. Não haverá migração de compatibilidade, cópia de colunas legadas nem implantação em duas fases; bancos locais dessa branch deverão ser recriados ou revertidos durante o desenvolvimento.

Na edição, `@` abrirá um autocomplete unificado com a visão inicial **Todos** e filtros para Pessoas, Projetos, Tarefas, Reuniões e Arquivos. Resultados serão agrupados e identificados pelo tipo, com entidades do contexto atual priorizadas, e pesquisados por seus nomes, títulos ou rótulos sem expor IDs ao autor. O seletor de Arquivos existente permanecerá como caminho adicional, mas passará a inserir a mesma sintaxe canônica de Menção.

A primeira entrega não incluirá tela de backlinks. Ela fornecerá o índice, os relacionamentos e consultas internas autorizadas nas duas direções, além da edição e renderização das Menções generalizadas; decisões de apresentação, agrupamento, paginação e localização de uma futura visão “Mencionado em” ficarão para uma entrega própria.

Menções a Reunião persistirão somente seu ID. Na renderização, a rota será construída com um Projeto vinculado que o leitor possa visualizar, reutilizando a seleção contextual existente da Reunião; se não houver Projeto acessível, a Menção será exibida como falta de permissão. O Projeto de contexto não será gravado no Markdown, permitindo que vínculos da Reunião mudem sem invalidar a Menção.

A tabela derivada não terá `created_by`, `created_at` nem `updated_at`. Datas de criação ou atualização das linhas mudariam em reconstruções e não representariam com fidelidade a criação da Menção nem a edição do texto. Autoria e cronologia permanecerão responsabilidades das fontes e de sua auditoria.

Toda Menção disponível será exibida de modo uniforme como `@{rótulo atual}`, independentemente de apontar para Usuário, Projeto, Tarefa, Reunião ou Arquivo. O tipo não integrará o texto visível normal da Menção.

Para distinguir tipos com rótulos iguais, o link fornecerá um nome acessível no formato `{tipo}: {rótulo}` e um tooltip equivalente, exibido no hover e no foco por teclado. O texto visível continuará inalterado. Em dispositivos de toque não será criada uma interação adicional apenas para inspecionar o tipo antes da navegação.

Os aliases estáveis de destino serão `user`, `project`, `task`, `meeting` e `file`; este último encapsulará o model técnico `Media`. Os aliases de origem serão `project`, `task`, `meeting`, `meeting_item` e `comment`. Nomes de classes não serão persistidos na sintaxe nem nas colunas polimórficas.

Qualquer construção com a forma `@[...](mention:...)` que use tipo não registrado ou chave malformada impedirá o salvamento e produzirá erro no respectivo campo. Sintaxes inválidas não serão degradadas silenciosamente para texto ou link comum, evitando que o autor acredite ter criado uma relação inexistente.

Ao salvar uma Menção nova bem formada, destino inexistente e destino não autorizado produzirão a mesma mensagem: `Uma ou mais Menções não existem ou não são permitidas neste contexto.` A validação não revelará qual condição ocorreu, evitando enumeração de entidades inacessíveis. A distinção visual entre falta de permissão e destino não encontrado aplica-se apenas à leitura de Menções históricas já persistidas.

Em textos de Reunião, Arquivos já acessíveis serão mencionados diretamente. Um Arquivo elegível que ainda dependa de compartilhamento aparecerá com a ação explícita **Compartilhar com a reunião e mencionar**, que persistirá o compartilhamento antes de inserir a sintaxe. A simples existência da Menção não concederá nem implicará compartilhamento.

Notificações, e-mails e eventos de acompanhamento permanecerão fora do escopo. Uma eventual funcionalidade futura deverá reagir especificamente a Menções a usuário e definir suas próprias regras de deduplicação, preferências e elegibilidade, sem atribuir efeito de notificação ao conceito geral de Menção.

A implementação concentrará pesquisa, sincronização, apresentação e reconstrução em um módulo concreto `MentionManager`. Como o próprio sistema via rotas web será o único consumidor, não haverá contrato PHP abstrato, porta HTTP adicional, repositório genérico nem API paralela. O módulo usará Eloquent, policies e transações existentes diretamente e esconderá, por adaptadores internos, somente as diferenças reais entre os cinco tipos de destino, inclusive chave, busca, autorização, rótulo e rota. Parser, controllers, renderizador e comando não duplicarão decisões por tipo.

Quando uma fonte sofrer exclusão lógica ou um Comentário ficar inativo, suas relações de saída serão removidas do índice sem alterar o Markdown. A restauração da fonte ou reativação do Comentário reconstruirá essas relações. Fontes indisponíveis, ao contrário de destinos restauráveis, não aparecerão em consultas de backlinks enquanto não puderem ser visitadas.

Projeto, Tarefa e Reunião não poderão mencionar a si próprios em seus próprios campos Markdown; essas opções serão omitidas da pesquisa e rejeitadas no salvamento. Um Comentário poderá mencionar o objeto comentado porque fonte e destino são entidades distintas.

Renomear uma Entidade mencionável não reescreverá os textos que apontam para ela. O Markdown preservará o Rótulo histórico da Menção, enquanto renderização, tooltip e nome acessível resolverão o rótulo atual. Reescrita automática, síncrona ou em fila, fica fora do escopo por exigir alteração segura e concorrente de múltiplos textos sem benefício para a exibição normal.

O salvamento do campo Markdown e a sincronização de suas relações ocorrerão na mesma transação. Análise, validação, persistência do texto, remoção de relações ausentes e criação de novas relações serão confirmadas ou revertidas em conjunto. `mentions:rebuild` será uma ferramenta idempotente de recuperação operacional, não o mecanismo normal de consistência.

Não haverá conversão de links comuns de Arquivos já gravados na branch. Novas inserções pelo seletor usarão `mention:file:UUID`; links Markdown existentes continuarão funcionando como links comuns e não integrarão o índice. A branch ainda não publicada permite recriar seus bancos sem introduzir um reescritor de conteúdo.

O autocomplete priorizará o contexto natural da fonte: Projeto oferecerá primeiro seus membros diretos, Tarefas, Reuniões vinculadas e Arquivos permitidos; Tarefa usará seu Projeto e seus próprios Arquivos; Reunião e Item de pauta usarão membros e Projetos vinculados, Tarefas da pauta e Arquivos próprios, compartilhados ou compartilháveis; Comentário herdará o objeto comentado. Em seguida, Projeto, Tarefa e Reunião poderão pesquisar outros destinos visíveis ao autor. Usuários e Arquivos permanecerão limitados às regras contextuais.

Duplicações copiarão o Markdown sem alterar suas Menções e só sincronizarão o índice depois de todos os vínculos do novo contexto terem sido persistidos, ainda dentro da mesma transação. As relações copiadas serão tratadas como históricas: não adicionarão membros, não compartilharão Arquivos e não concederão acesso. Tarefas e Reuniões mencionadas continuarão apontando para as entidades originais mesmo quando elas também tiverem sido duplicadas; remapear chaves para as cópias fica fora do escopo. Cada leitor verá o link original se tiver autorização ou a mensagem correspondente de falta de permissão.
