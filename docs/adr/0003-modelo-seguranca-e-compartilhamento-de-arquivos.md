# Modelo, segurança e compartilhamento de Arquivos

**Status:** aceito

Esta decisão reúne identidade, propriedade, ciclo de vida, segurança e compartilhamento de **Arquivos**. Cada Arquivo terá um único Proprietário imutável e conteúdo binário imutável; referências usarão UUID, não concederão acesso por si mesmas e somente o Compartilhamento de arquivo com reunião criará uma exceção explícita de acesso.

## Contexto

O módulo precisa armazenar formatos gerais ligados a projetos, tarefas ou reuniões, manter autorização e ciclo de vida inequívocos e permitir referências persistentes em textos. Reuniões também precisam disponibilizar Arquivos específicos de objetos relacionados a uma audiência conjunta, sem transferir propriedade nem liberar todo o conjunto da origem.

Como o sistema não fará análise antivírus e aceitará inclusive formatos ativos ou executáveis para download, armazenamento privado e autorização não podem ser apresentados como certificação de segurança. A exibição inline e as conversões precisam permanecer deliberadamente restritas.

## Decisões

### Propriedade, identidade e referências

- Cada Arquivo terá exatamente um **Proprietário do arquivo** — projeto, tarefa ou reunião — definido na criação e imutável.
- Textos de outras entidades poderão criar Referências de arquivo sem compartilhar sua propriedade. Referências não concederão acesso por si mesmas.
- Rotas e Referências de arquivo usarão o UUID estável do Arquivo, enquanto o identificador numérico permanecerá restrito às relações internas do banco.
- O UUID não substituirá a autorização, mas evitará acoplar textos persistidos a identificadores sequenciais e reduzirá a exposição acidental da quantidade de Arquivos.

### Conteúdo e ciclo de vida

- O conteúdo binário de um Arquivo será imutável: não haverá substituição mantendo o mesmo UUID, e uma revisão deverá ser enviada como um novo Arquivo.
- A primeira versão não agrupará revisões nem oferecerá histórico automático de versões.
- A exclusão lógica do Proprietário do arquivo preservará seus Arquivos, que ficarão inacessíveis pelas rotas normais e voltarão a ser acessíveis se o Proprietário for restaurado.
- A exclusão definitiva do Proprietário removerá seus Arquivos, metadados e conversões.
- A exclusão individual de um Arquivo também será definitiva e auditada, sem `deleted_at`, restauração ou lixeira própria.

### Formatos e fronteira de segurança

- O módulo aceitará formatos gerais de Arquivo, sem uma lista fechada de extensões ou MIME e sem análise antivírus presente ou futura.
- A detecção de MIME será armazenada como metadado e poderá orientar apresentação e conversões, mas nunca será apresentada como certificação de segurança.
- A proteção nativa da Media Library contra extensões de scripts executáveis pelo servidor em qualquer segmento do nome será mantida. Formatos como `.exe`, `.bat` e `.sh` continuarão aceitos para download.
- Somente imagens raster validadas e decodificáveis poderão ser exibidas inline. Os demais formatos serão entregues como download com `Content-Disposition: attachment` e `X-Content-Type-Options: nosniff`; SVG, HTML, PDF e outros conteúdos ativos não serão incorporados nas páginas.
- O nome físico será opaco e separado do Nome exibido do arquivo. Nomes recebidos serão normalizados para impedir travessia de diretórios, quebra de cabeçalhos e extensões de scripts executáveis pelo servidor disfarçadas, sem substituir o sanitizador seguro da biblioteca.
- O Nome original do arquivo será preservado como metadado imutável e restrito a quem puder administrar o Arquivo. Essa preservação registrará o que foi efetivamente enviado, permitirá investigar extensões duplas ou divergências de MIME e manterá a proveniência após uma renomeação; o nome original não participará de URLs, caminhos físicos nem cabeçalhos de download.
- Arquivos compactados não serão extraídos pelo servidor. Conversões ficarão restritas a imagens raster, com limites de dimensões e memória para reduzir o risco de esgotamento de recursos.
- Upload, renomeação e exclusão serão auditados.

### Controles compensatórios e riscos aceitos

- Um Arquivo autorizado ainda pode conter malware, macros, conteúdo ativo, arquivos poliglotas ou cargas maliciosas dentro de formatos e contêineres válidos. Usuários não devem interpretar a presença do Arquivo no sistema como garantia de segurança.
- O armazenamento privado e a autorização reduzem exposição, mas não impedem que um usuário autorizado envie ou baixe conteúdo malicioso.
- O limite de 100 MB exige monitoramento de capacidade, backups e falhas de transferência.
- A ausência inicial de cotas por usuário ou Proprietário do arquivo será tratada como risco operacional conhecido, não como armazenamento ilimitado.

### Hierarquia de projetos

- A hierarquia e a herança de permissões entre projetos controlarão o acesso de pessoas, mas não ampliarão o escopo de Arquivos nem de Referências de arquivo.
- Um texto de subprojeto ou de suas tarefas não poderá referenciar Arquivos do projeto pai apenas por causa da hierarquia, e o projeto pai também não receberá acesso aos Arquivos dos filhos.
- Quando um conteúdo precisar pertencer ao contexto do subprojeto, deverá ser enviado como um novo Arquivo desse subprojeto.

Essa separação é necessária porque um usuário pode ser membro direto do subprojeto sem visualizar o pai. Permitir a referência criaria textos cujo Arquivo estaria disponível para alguns leitores e indisponível para outros dentro da mesma entidade.

### Compartilhamento com reuniões

- Uma reunião poderá compartilhar explicitamente Arquivos pertencentes aos projetos vinculados ou incluídos como itens de pauta e às tarefas incluídas em sua pauta.
- O compartilhamento concederá acesso a todos que puderem visualizar a reunião, sem transferir o Proprietário do arquivo nem liberar automaticamente outros Arquivos dos mesmos objetos relacionados.
- Essa exceção à regra geral de que referências não concedem acesso atenderá à audiência conjunta de reuniões com participantes de projetos diferentes sem transformar o texto editável em mecanismo de autorização. Arquivos não compartilhados continuarão sujeitos exclusivamente ao acesso derivado de seu Proprietário.
- O compartilhamento será uma relação persistida e independente do Markdown. O seletor poderá executar a ação **Compartilhar com a reunião e inserir**, mas remover uma Referência de arquivo do texto não revogará acesso.
- A revogação exigirá a ação própria **Remover da reunião** no card de Arquivos. Revogar não excluirá o original, enquanto excluir o Arquivo ou a reunião removerá a relação automaticamente.
- Para compartilhar, o usuário deverá poder editar a reunião e visualizar o Arquivo de origem. Qualquer colaborador autorizado a editar a reunião poderá remover o compartilhamento, enquanto visualizadores apenas acessarão o conjunto já compartilhado.
- Projetos e tarefas não excluídos poderão fornecer Arquivos independentemente de status, pois a operação não altera a origem nem o conteúdo.
- Mudanças posteriores na pauta, nos projetos vinculados ou na habilitação de módulos não revogarão compartilhamentos existentes.
- A exclusão lógica do Proprietário tornará o Arquivo temporariamente indisponível e sua restauração recuperará o acesso. Fora disso, o compartilhamento persistirá até remoção explícita ou exclusão definitiva do Arquivo ou da reunião.

## Opções consideradas

- **Permitir múltiplos Proprietários para o mesmo Arquivo:** rejeitada porque tornaria autorização e ciclo de vida ambíguos.
- **Substituir o binário preservando UUID e referências:** rejeitada porque uma URL existente passaria a entregar conteúdo diferente sem mudança de identidade.
- **Compartilhar Arquivos pela hierarquia de projetos:** rejeitada porque a herança de acesso de pessoas não garante que todos os leitores de uma entidade possam acessar o mesmo Arquivo.
- **Usar a Referência de arquivo no Markdown como mecanismo de autorização:** rejeitada porque edição de texto passaria a conceder e revogar acesso implicitamente.
- **Incorporar formatos ativos nas páginas:** rejeitada porque armazenamento privado, MIME detectado e autorização não certificam que o conteúdo seja seguro.

## Consequências

O módulo terá uma identidade estável e uma fronteira de autorização única, enquanto o compartilhamento com reuniões permanecerá explícito, auditável e independente do conteúdo editável. Em contrapartida, revisões ocuparão novos registros, referências poderão permanecer quebradas após exclusões e a operação sem antivírus exigirá que os controles compensatórios e os riscos aceitos permaneçam visíveis na interface e na documentação operacional.
