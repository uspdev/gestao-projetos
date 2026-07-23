# Editor Markdown, Arquivos e Menções

## Declaração do problema

O sistema já armazena Markdown em descrições, comentários e Anotações prévias, mas a edição ainda ocorre em áreas de texto simples e a renderização atual não estabelece uma fronteira de segurança confiável. O helper `md2html()` é global, injeta CSS a cada uso, depende de um realçador abandonado e não oferece uma política única para HTML, URLs, imagens e extensões futuras.

Também não existe um módulo de Arquivos. Os usuários não conseguem enviar, administrar e referenciar documentos relacionados a projetos, tarefas e reuniões com autorização e ciclo de vida definidos. Reuniões vinculadas a mais de um projeto precisam compartilhar Arquivos específicos com toda a audiência sem transferir sua propriedade ou liberar todos os Arquivos dos objetos relacionados.

Por fim, textos não possuem Menções estruturadas. Digitar um nome não cria identidade estável nem permite indexar quem foi mencionado. O autor não deve precisar conhecer identificadores numéricos, e o Markdown precisa continuar sendo a fonte editorial da verdade.

## Solução

Criar um serviço único e injetável de renderização segura com `league/commonmark`, acompanhado de dois perfis locais do EasyMDE e uma pré-visualização produzida pelo servidor. Aplicar a mesma política a todos os campos Markdown da primeira versão, incluindo Anotações prévias da reunião e do item, mantendo Ata e Transcrição como texto simples.

Introduzir um módulo de Arquivos privados baseado em `spatie/laravel-medialibrary`, com Projeto, Tarefa ou Reunião como Proprietário único e imutável. Cada Arquivo terá UUID público estável, nome original imutável, nome exibido editável, conteúdo imutável, autor do envio e nome físico opaco. Downloads sempre passarão por autorização da aplicação. Imagens raster válidas poderão gerar miniaturas privadas assíncronas; outros formatos serão entregues somente como download.

Permitir Referências de arquivo nos textos por links Markdown comuns. Uma reunião poderá persistir Compartilhamentos de arquivo com reunião para liberar Arquivos específicos de projetos e tarefas relacionados a todos que puderem visualizar a reunião, sem alterar a propriedade original.

Adicionar Menções de usuários com autocomplete e sintaxe `@[Nome](mention:user:ID)`. O Markdown bruto continuará sendo a fonte da verdade, enquanto a tabela `mentions` funcionará como índice derivado reconstruível.

## Plano de implementação

A entrega seguirá uma sequência deliberadamente linear para estabilizar primeiro a fronteira de renderização e só então adicionar recursos que dependem dela:

1. [Centralizar a renderização Markdown segura](issues/01-centralizar-renderizacao-markdown-segura.md): serviço oficial, política de segurança, CSS, campos consumidores e conversão legada.
2. [Integrar editor e pré-visualização oficial](issues/02-integrar-editor-e-preview-oficial.md): ativos locais, dois perfis do EasyMDE e endpoint de pré-visualização.
3. [Criar persistência e processamento de Arquivos](issues/03-criar-persistencia-e-processamento-de-arquivos.md): Media Library, disk privado, metadados, ciclo de vida e miniaturas.
4. [Expor Arquivos com autorização, cards e auditoria](issues/04-expor-arquivos-com-autorizacao-e-auditoria.md): política de autorização, rotas, operações e interface reutilizável.
5. [Integrar Referências de arquivo e compartilhamento com Reuniões](issues/05-integrar-referencias-e-compartilhamento-com-reunioes.md): seletores, links por UUID e audiência conjunta persistida.
6. [Implementar Menções e índice derivado](issues/06-implementar-mencoes-e-indice-derivado.md): autocomplete, sintaxe, renderização, validação e sincronização.
7. [Validar integração e documentar implantação](issues/07-validar-integracao-e-documentar-implantacao.md): regressão completa, homologação e instruções operacionais.

Cada ticket depende do anterior. Essa ordem preserva os marcos aprovados na sessão e impede que editor, Arquivos, compartilhamentos ou Menções criem caminhos paralelos de renderização e autorização.

## Histórias de usuário

### Markdown e edição

1. Como autor, quero editar os campos Markdown com uma barra de ferramentas adequada ao contexto, para formatar conteúdo sem memorizar toda a sintaxe.
2. Como autor, quero consultar uma pré-visualização fiel à renderização oficial antes de salvar, para identificar erros de formatação.
3. Como leitor, quero que HTML bruto, URLs perigosas e imagens externas não executem conteúdo ativo, para consultar textos sem ampliar a superfície de ataque.
4. Como leitor, quero que todos os links abram em nova aba, para preservar a página atual do sistema.
5. Como usuário, quero que Anotações prévias da reunião e dos itens aceitem o mesmo Markdown, para ter uma experiência coerente.
6. Como usuário, quero que Ata e Transcrição permaneçam texto simples, para não alterar o significado já definido desses registros.
7. Como equipe de manutenção, quero uma única implementação de renderização, para evitar diferenças de segurança entre telas.

### Arquivos

8. Como colaborador, quero enviar um Arquivo de até 100 MB a um Projeto, Tarefa ou Reunião, para armazená-lo no contexto correto.
9. Como visualizador, quero listar, visualizar metadados e baixar os Arquivos a que tenho acesso, para consultar o material do trabalho.
10. Como autor do envio, quero renomear ou excluir definitivamente meu Arquivo enquanto ainda tiver acesso ao Proprietário, para administrar o que enviei.
11. Como administrador relacionado ao Proprietário, quero administrar qualquer Arquivo daquele contexto, para moderar o conteúdo.
12. Como usuário, quero distinguir o nome exibido do nome original do envio, para permitir renomeações sem perder proveniência.
13. Como leitor, quero ver uma miniatura de imagens raster válidas e um card genérico nos demais casos, para reconhecer o conteúdo sem incorporar formatos ativos.
14. Como usuário, quero que a exclusão lógica do Proprietário apenas suspenda o acesso e que sua restauração o recupere, para manter o mesmo ciclo de vida do objeto.
15. Como administrador, quero que a exclusão definitiva do Proprietário ou a exclusão individual do Arquivo remova conteúdo, metadados e conversões, para não deixar resíduos.
16. Como usuário, quero enviar uma revisão como um novo Arquivo, para que uma URL existente nunca passe a entregar conteúdo binário diferente.

### Referências e reuniões

17. Como autor, quero selecionar um Arquivo permitido e inserir uma Referência de arquivo no Markdown, para criar o link sem conhecer o UUID.
18. Como leitor, quero que uma referência ausente ou não autorizada resulte no mesmo não encontrado, para não revelar a existência do Arquivo.
19. Como colaborador de reunião, quero compartilhar explicitamente um Arquivo relacionado e inseri-lo no texto, para disponibilizá-lo a toda a audiência da reunião.
20. Como visualizador da reunião, quero acessar os Arquivos próprios e os explicitamente compartilhados, mesmo quando pertençam a outro projeto vinculado, para consultar todo o material daquela reunião.
21. Como colaborador de reunião, quero remover um compartilhamento sem excluir o Arquivo original, para revogar apenas o acesso adicional.
22. Como usuário, quero que mudanças posteriores de pauta, projetos ou módulos não apaguem compartilhamentos históricos da reunião, para preservar seu registro.

### Menções

23. Como autor, quero iniciar uma busca digitando `@` e parte do nome, para encontrar usuários elegíveis sem conhecer seus IDs.
24. Como autor, quero criar a Menção apenas após selecionar um resultado, para que texto comum com `@` não ganhe semântica acidental.
25. Como leitor, quero ver o nome atual do usuário mencionado, para reconhecer a pessoa mesmo depois de uma alteração cadastral.
26. Como leitor, quero ver `@Usuário indisponível` quando a identidade não puder ser revelada, para não expor usuários fora do meu alcance.
27. Como equipe de manutenção, quero indexar Menções de forma reconstruível, para permitir consultas futuras sem transformar a tabela em fonte editorial.

## Campos Markdown da primeira versão

| Contexto | Campo | Perfil do editor | Limite | Pode selecionar Arquivos existentes antes de persistir? |
|---|---|---|---:|---|
| Projeto | `description` | completo | 10.000 | não, na criação |
| Tarefa | `description` | completo | 10.000 | sim, Arquivos do Projeto |
| Comentário | `text` | compacto | 10.000 | sim, conjunto permitido pelo objeto comentado |
| Reunião | `notes` — Anotações prévias | completo | 10.000 | não, na criação |
| Item de pauta | `notes` — Anotações prévias do item | completo | 10.000 | sim, conjunto permitido pela Reunião |

Descrições de Tipo de projeto serão renderizadas pela mesma política segura e o conteúdo HTML legado conhecido será convertido para Markdown, mas esta entrega não criará CRUD nem editor administrativo novo para Tipos de projeto. Ata, Transcrição e e-mails permanecem exatamente com o comportamento atual.

## Política de renderização Markdown

- Criar `MarkdownRenderer` como serviço injetável de instância única na aplicação. O componente Blade e os usos administrativos deverão delegar a ele.
- Remover o uso do helper global `md2html()` no código da aplicação e retirar sua implementação local ineficaz depois da migração dos consumidores. `text2html()` continuará atendendo os campos de texto simples.
- Usar GitHub Flavored Markdown com `html_input: escape`, bloqueio de URLs inseguras e `max_nesting_level` igual a 20.
- Admitir em links somente URLs relativas, âncoras `#...`, `http` e `https`. `mailto:`, `javascript:`, `data:` e qualquer outro esquema ficarão fora da lista permitida.
- Todos os links, internos ou externos, receberão `target="_blank"` e `rel="noopener noreferrer"`.
- Não incorporar imagens declaradas no Markdown na primeira versão. A sintaxe de imagem será degradada para um link seguro; miniaturas aparecerão apenas nos cards de Arquivo. Isso inclui imagens externas.
- Resolver Menções e aplicar regras de links na árvore de sintaxe abstrata, antes da emissão do HTML; não reescrever HTML final com expressões regulares.
- Emitir classes de linguagem nos blocos de código. O realce será feito no navegador por `highlight.js`, empacotado localmente, sem HTML de realce produzido pelo servidor.
- Centralizar o CSS do conteúdo renderizado nos ativos da aplicação; não injetar folhas de estilo dentro de cada conteúdo.

## Editor e pré-visualização

- Empacotar EasyMDE e `highlight.js` localmente por Laravel Mix, com versões registradas em `package-lock.json`; não usar CDN.
- Habilitar o corretor ortográfico do navegador e desabilitar o autosave do EasyMDE.
- O perfil completo terá negrito, itálico, títulos, citação, listas ordenada, não ordenada e de tarefas, link, código inline e em bloco, tabela, Menção, Referência de arquivo, pré-visualização e ajuda curta.
- O perfil compacto terá negrito, itálico, citação, listas ordenada e não ordenada, link, código inline e em bloco, Menção, Referência de arquivo e pré-visualização.
- Não oferecer botão de imagem externa, upload genérico do EasyMDE, arrastar e soltar, colagem de arquivo, tela cheia nem edição lado a lado.
- A rota de pré-visualização ficará no grupo web autenticado e seguirá exatamente os padrões existentes de autenticação e proteção CSRF, sem controles adicionais e sem limite específico de requisições.
- Enquanto a pré-visualização estiver visível, o cliente enviará o conteúdo após 500 ms sem digitação, cancelará ou ignorará respostas obsoletas e manterá a última resposta válida se houver falha.
- A pré-visualização aceitará no máximo 10.000 caracteres, aplicará o mesmo limite de aninhamento e retornará somente o HTML oficial; não persistirá conteúdo nem produzirá auditoria.

## Modelo de Arquivos

### Persistência

- Adotar `spatie/laravel-medialibrary` compatível com Laravel 12 e um modelo `Media` próprio da aplicação.
- Usar a relação polimórfica padrão da biblioteca para o Proprietário do arquivo, limitada por contrato de aplicação a `Project`, `Task` e `Meeting`.
- Manter o ID numérico para relações internas e o UUID único para rotas e referências públicas.
- Usar `media.name` como Nome exibido do arquivo e `media.file_name` como nome físico opaco e imutável no formato UUID mais extensão normalizada.
- Acrescentar metadados próprios para Nome original do arquivo e Autor do arquivo. O nome original será imutável e visível somente a usuários que possam administrar o Arquivo; exclusão do usuário autor não excluirá o Arquivo.
- O conteúdo binário, UUID, Proprietário, Nome original e nome físico serão imutáveis. Renomear alterará somente o Nome exibido do arquivo.
- Nomes exibidos duplicados serão permitidos. O nome sugerido no download será composto de forma segura pelo Nome exibido e pela extensão submetida/normalizada, sem usar o caminho físico.
- Não adicionar `deleted_at` a Arquivos. A exclusão individual será definitiva e exigirá confirmação explícita.

### Armazenamento e formatos

- Criar um disk dedicado e privado para Arquivos, inicialmente local e configurável por ambiente. Nenhum conteúdo dependerá de `storage:link` ou de URL pública do disk; uma futura troca para S3 não deverá alterar o contrato das rotas.
- Limitar cada envio a 100 MB por configuração da aplicação. A implantação deverá alinhar limites de PHP, servidor web/proxy e infraestrutura; falhas nesses limites não serão contornadas por upload em partes.
- Aceitar um Arquivo por requisição e formatos gerais, sem lista fechada de extensões ou MIME e sem análise antivírus presente ou futura.
- Preservar o bloqueio e a sanitização nativos da Media Library para extensões de scripts executáveis pelo servidor. Formatos como `.exe`, `.bat` e `.sh` continuarão aceitos para download.
- Registrar MIME detectado apenas como metadado, nunca como certificado de segurança. Não extrair arquivos compactados.
- Entregar todo original com `Content-Disposition: attachment` e `X-Content-Type-Options: nosniff`. SVG, HTML, PDF e outros conteúdos ativos nunca serão incorporados às páginas.

### Miniaturas

- Gerar miniaturas privadas somente para imagens raster que o GD consiga decodificar com segurança.
- Recusar conversão acima de 25 megapixels ou quando largura ou altura exceder 10.000 pixels.
- Processar a conversão em fila depois da confirmação da transação. Exibir estado de processamento e substituir por card genérico em formato não suportado ou falha definitiva.
- Gerar apenas o primeiro quadro de GIF animado. O original permanecerá inalterado e disponível para download autorizado mesmo quando a conversão falhar.

### Ciclo de vida e auditoria

- A exclusão lógica do Proprietário preservará seus Arquivos, mas listagem, metadados, miniaturas e download responderão como não encontrados nas rotas normais.
- A restauração do Proprietário recuperará automaticamente o acesso. A exclusão definitiva do Proprietário removerá metadados, originais, miniaturas e compartilhamentos.
- Upload, renomeação e exclusão serão registrados no `activity_log`, sem copiar conteúdo binário. Listagens, visualizações e downloads não gerarão eventos de atividade; logs técnicos do servidor permanecem inalterados.
- Não haverá substituição de conteúdo, versionamento automático, agrupamento de revisões, lixeira, cotas, descrição, etiquetas, comentários ou ordenação manual de Arquivos.

## Autorização de Arquivos

Toda decisão deverá ser centralizada em uma política ou serviço de autorização reutilizado por listagem, seletor, miniatura, metadados e download.

| Operação | Regra inicial |
|---|---|
| Listar, consultar metadados, miniatura e baixar | qualquer usuário que possa visualizar o Proprietário; ou visualizar a Reunião que recebeu compartilhamento explícito |
| Enviar | colaborador do Projeto relacionado ao Proprietário; em Tarefa `DONE`, negar; Reunião `COMPLETED` continua permitindo |
| Renomear e excluir | Autor do arquivo que ainda tenha acesso ao Proprietário, ou administrador de Projeto relacionado, respeitando o bloqueio de Tarefa `DONE` |
| Compartilhar com Reunião | usuário que possa editar a Reunião e visualizar o Arquivo de origem |
| Remover da Reunião | qualquer colaborador que possa editar a Reunião |
| Administração global | mantém o comportamento de sobreposição administrativa já adotado pela aplicação |

Para Arquivo de Projeto, o Projeto relacionado é o próprio Proprietário; para Arquivo de Tarefa, é o Projeto da Tarefa; para Arquivo de Reunião, são os Projetos vinculados. Reuniões concluídas não bloqueiam mutações de Arquivos. O estado `DONE` bloqueia envio, renomeação e exclusão em Tarefas até sua reabertura, mas nunca leitura ou download.

## Listagem e card de Arquivos

- Exibir uma lista reutilizável e responsiva nas telas de Projeto, Tarefa e Reunião, sem galeria separada.
- Ordenar do mais novo para o mais antigo e paginar em 20 itens.
- Exibir miniatura ou ícone genérico, Nome exibido, extensão, tamanho, Autor do arquivo, data, estado da miniatura e ações permitidas.
- Exibir o formulário de upload no cabeçalho quando autorizado. Cada envio será unitário.
- Em Reuniões, distinguir Arquivos próprios de Arquivos compartilhados e oferecer remoção do compartilhamento sem sugerir exclusão do original.
- A confirmação de exclusão individual deverá informar que a ação é definitiva e que referências existentes poderão deixar de funcionar.

## Referências de arquivo

- Persistir inicialmente uma Referência de arquivo como link Markdown comum: `[Nome exibido](/files/{uuid})`.
- O rótulo persistido será histórico: renomear o Arquivo não reescreverá textos existentes. Excluir o Arquivo também não reescreverá conteúdo.
- Não criar tabela derivada de referências na primeira versão. Links quebrados são uma consequência aceita.
- O seletor mostrará apenas Arquivos que o autor possa visualizar no contexto de destino:

| Texto de destino | Arquivos selecionáveis |
|---|---|
| Projeto | Arquivos do próprio Projeto |
| Tarefa | Arquivos da Tarefa e do mesmo Projeto |
| Comentário de Projeto/Tarefa | mesmo conjunto do objeto comentado |
| Reunião, Item de pauta ou Comentário de Reunião | Arquivos próprios da Reunião e Arquivos explicitamente compartilhados |

- A hierarquia entre Projetos não amplia o seletor: pai, filho e irmãos não compartilham Arquivos por herança.
- Formulários de novos Projetos e novas Reuniões não terão seletor antes da persistência. Novas Tarefas poderão selecionar Arquivos existentes do Projeto e novos Comentários poderão selecionar Arquivos do objeto comentado. Não haverá token de rascunho.
- Para trazer um Arquivo de Projeto vinculado ou presente na pauta, ou de Tarefa presente na pauta, para o contexto da Reunião, o colaborador usará **Compartilhar com a reunião e inserir**. A ação persistirá o compartilhamento antes de inserir o link.
- Arquivos já compartilhados aparecerão somente no conjunto selecionável da Reunião, e não como novos candidatos de compartilhamento.
- Os candidatos a compartilhamento serão agrupados pela origem concreta: Projeto vinculado, Projeto na pauta ou Tarefa na pauta, identificando o respectivo nome.
- Endpoints por UUID retornarão a mesma resposta de não encontrado para UUID inexistente, Arquivo inacessível, Proprietário excluído ou compartilhamento ausente.

## Compartilhamento de arquivo com reunião

- Persistir uma relação única entre Reunião e Arquivo, com o usuário que criou o compartilhamento e datas.
- Permitir como origem Arquivos de Projetos vinculados ou incluídos na pauta e de Tarefas incluídas na pauta, desde que o Proprietário não esteja excluído. O status do Projeto ou da Tarefa de origem não impede compartilhar um Arquivo existente.
- O compartilhamento concede leitura a todos que possam visualizar a Reunião; não transfere propriedade, não duplica conteúdo e não autoriza outros Arquivos da origem.
- Remover o link do Markdown não revoga o compartilhamento. A ação **Remover da reunião** revoga apenas o acesso adicional e não altera o texto nem exclui o Arquivo.
- Mudanças posteriores na pauta, Projetos vinculados, status ou módulos não revogam relações existentes.
- A exclusão lógica do Proprietário suspende temporariamente o acesso compartilhado; a restauração o recupera. Exclusão definitiva do Arquivo ou da Reunião remove a relação.

## Menções

### Sintaxe, edição e exibição

- Suportar somente Menção a usuário na primeira versão, com sintaxe `@[Rótulo histórico](mention:user:ID_NUMERICO)`.
- Abrir o autocomplete quando o usuário digitar `@` seguido de texto. Permitir seleção por clique, setas com `Enter` ou `Tab`; digitar o nome completo sem seleção continuará sendo texto comum.
- O autor nunca informará nem visualizará o ID como parte do fluxo de seleção.
- Na renderização, resolver o ID para o nome atual sem reescrever o Markdown. Se o usuário não existir ou não puder ser revelado ao leitor, emitir `@Usuário indisponível` sem link.
- Identificar Menções nos nós do parser, não por expressão regular no HTML.

### Elegibilidade

| Contexto | Usuários oferecidos e aceitos em novas Menções |
|---|---|
| Projeto | membros diretamente vinculados ao Projeto |
| Tarefa | membros diretamente vinculados ao Projeto da Tarefa |
| Reunião e Item de pauta | união dos membros diretamente vinculados aos Projetos da Reunião |
| Comentário | conjunto do objeto comentado |

Usuários com acesso apenas herdado não serão elegíveis. O próprio autor poderá ser selecionado. No salvamento, validar somente IDs recém-adicionados; Menções históricas que perderem elegibilidade permanecerão no Markdown e no índice, mas poderão ser exibidas como indisponíveis.

### Índice derivado

- Criar `mentions` com fonte polimórfica, nome do campo textual, usuário mencionado, autor que criou a relação e datas.
- Garantir unicidade por fonte, campo e usuário; ocorrências repetidas no mesmo campo produzirão uma linha.
- Sincronizar dentro da mesma transação do salvamento: criar relações novas, remover ausentes e preservar relações já existentes para manter seu autor e data originais.
- Quando uma Menção removida for adicionada novamente, criar nova relação com novo autor e nova data.
- Limpar o índice quando a fonte deixar de existir ou ficar inativa; ao restaurar uma fonte que use exclusão lógica, reconstruir suas relações a partir do Markdown.
- Fornecer comando idempotente para reconstruir o índice de todos os campos Markdown, permitindo recuperar divergências.
- Não criar notificações, e-mails, caixa de entrada, backlinks nem tela de consulta de Menções nesta entrega.

## Modelo de dados adicional

### Extensões do modelo `media`

- UUID público único e ID numérico interno, conforme a estrutura da Media Library.
- `original_name`: Nome original do arquivo, obrigatório e imutável.
- `uploaded_by`: referência opcional ao usuário autor, preservando o Arquivo caso a conta deixe de existir.
- Índices necessários para proprietário, UUID, autor e ordenação cronológica.

### `meeting_file_shares`

- `meeting_id`, `media_id`, `shared_by`, datas.
- Unicidade por Reunião e Arquivo.
- Remoção em cascata na exclusão definitiva da Reunião ou do Arquivo; a conta de quem compartilhou poderá ser anulada sem remover a relação.

### `mentions`

- `mentionable_type`, `mentionable_id`, `field`, `mentioned_user_id`, `created_by`, datas.
- Unicidade por tipo, ID, campo e usuário mencionado.
- Índices para usuário mencionado e para reconstrução/limpeza por fonte.
- Usuário mencionado ou autor removido não poderá provocar exclusão do texto-fonte; as chaves deverão seguir a estratégia de preservação já usada pelo cadastro de usuários.

## Decisões de teste

- Priorizar testes HTTP de funcionalidade para autorização, validação, persistência, resposta não encontrada, auditoria e comportamento por status.
- Testar `MarkdownRenderer` isoladamente com matriz de XSS, HTML, esquemas de URL, âncoras, links relativos, imagens, aninhamento, Menções e blocos de código.
- Testar a conversão protegida do conteúdo legado de Tipo de projeto e garantir idempotência/reversão segura compatível com os dados.
- Usar `Storage::fake()` para upload, download, exclusão e conversões; usar `Queue::fake()` para despacho e testes separados da tarefa de miniatura executada em fila.
- Cobrir Arquivos de cada Proprietário, cada papel, Autor do arquivo, administrador, tarefa `DONE`, reunião `COMPLETED`, exclusão lógica, restauração e exclusão definitiva.
- Cobrir conteúdo malicioso renomeado, extensões de scripts bloqueadas, formatos gerais aceitos, cabeçalhos de download, limites de tamanho e dimensões de imagem.
- Cobrir seletores e referências para todos os contextos, ausência de herança e resposta indistinguível de não encontrado.
- Cobrir criação, persistência, revogação e estabilidade histórica de Compartilhamentos de arquivo com reunião, inclusive reuniões multiprojeto.
- Cobrir autocomplete, navegação por teclado, sintaxe, validação de novas Menções, preservação histórica, usuário indisponível, sincronização e reconstrução do índice.
- Criar fluxos Dusk para o editor/pré-visualização, upload/card/download, compartilhamento em reunião e autocomplete de Menções. Evitar depender de serviços externos.

## Implantação e operação

- Publicar migrações e configuração antes de habilitar as interfaces que dependem delas.
- Documentar `FILESYSTEM`/disk privado, limite de 100 MB, extensões GD necessárias e conexão de fila. O processador da fila precisa estar ativo quando a conexão não for `sync`.
- Validar limites de PHP e servidor web/proxy com um Arquivo próximo de 100 MB no ambiente de homologação.
- Compilar e publicar os ativos locais do EasyMDE e `highlight.js`.
- Executar a conversão do conteúdo legado de Tipo de projeto com verificação antes/depois.
- Não executar implantação, alterar infraestrutura nem criar storage público como parte dos tickets de código.

## Fora do escopo

- Alterar o funcionamento de e-mails existente ou enviar conteúdo Markdown, Arquivos ou Menções por e-mail.
- Notificações de Menção por qualquer canal.
- Antivírus presente ou futuro, análise profunda de conteúdo ou certificação de segurança por MIME.
- Limite específico de requisições para pré-visualização ou autocomplete além do padrão atual da aplicação.
- Upload em partes, múltiplos Arquivos por requisição, colagem, arrastar e soltar ou Arquivos de rascunho.
- Armazenamento público, links diretos do disk ou URLs permanentes sem autorização.
- Cotas, lixeira de Arquivos, substituição binária, versionamento, agrupamento de revisões ou histórico de downloads.
- Galeria separada, ordenação manual, descrição, etiquetas ou comentários próprios de Arquivos.
- Incorporação inline de PDF, SVG, HTML, documentos de escritório ou imagens declaradas no Markdown.
- Proprietários de Arquivo além de Projeto, Tarefa e Reunião.
- Referências estruturadas a Projeto, Tarefa ou Reunião; esses objetos continuarão usando links comuns.
- Tabela derivada de Referências de arquivo.
- Caixa de entrada, backlinks ou telas de consulta baseadas em `mentions`.
- Novo CRUD/editor para Tipo de projeto.
- Mudança de ACL, autenticação, CSRF, hierarquia de Projetos, status, e-mails ou infraestrutura fora do necessário para seguir os padrões existentes.

## Decisões arquiteturais relacionadas

- ADR 0002 — Markdown e Menções nos campos textuais.
- ADR 0003 — Modelo, segurança e compartilhamento de Arquivos.
