# Sistemas clientes como proprietários das Chaves de API

**Status:** aceito

Cada integração externa será representada por um Sistema cliente estável vinculado a um Projeto. O Sistema cliente será o proprietário das suas Chaves de API e das suas Solicitações, permitindo renovar credenciais sem perder identidade, histórico ou isolamento entre integrações.

## Contexto

O package `uspdev/api-keys` cria um novo registro ao renovar uma credencial e revoga o anterior, preservando apenas o mesmo owner. O nome da chave é editável e não é único; portanto, nem o registro da credencial nem seu nome representam de forma segura e permanente sistemas como Chamados ou Equivalência.

Um mesmo Projeto poderá receber Solicitações de vários sistemas, e uma integração não deverá consultar as Solicitações de outra apenas porque ambas atuam no mesmo Projeto.

## Decisão

- Um Sistema cliente pertencerá a exatamente um Projeto.
- O cadastro terá nome obrigatório entre 3 e 120 caracteres, único dentro do
  Projeto, e descrição opcional em texto simples com até 1.000 caracteres.
- O cadastro manterá autoria de criação, autoria da última alteração e
  timestamps para auditoria interna.
- Alterar o nome ou a descrição não mudará a identidade, as chaves nem o
  histórico do Sistema cliente.
- URL, contato, estado ativo e configurações de callback não integrarão o
  cadastro inicial.
- Um Sistema cliente poderá possuir uma ou mais Chaves de API ao longo do seu
  ciclo de vida.
- O Sistema cliente será o owner registrado pelo package `uspdev/api-keys`.
- Renovar uma Chave de API preservará o mesmo Sistema cliente como owner.
- Cada Solicitação pertencerá ao Sistema cliente que a enviou e ao Projeto
  desse sistema.
- Uma Chave de API poderá acessar somente as Solicitações do seu Sistema
  cliente e os recursos do seu Projeto.
- A URL continuará identificando explicitamente o Projeto pelo `slug`; a
  aplicação confirmará que o Sistema cliente autenticado pertence a esse
  Projeto.
- Recursos de outro Projeto ou de outro Sistema cliente serão respondidos
  como não encontrados, sem revelar sua existência ao consumidor.
- Somente administradores locais do Projeto e administradores globais da
  aplicação poderão criar e alterar Sistemas clientes e emitir, renovar ou
  revogar suas Chaves de API.
- Contribuidores, visualizadores e pessoas com acesso apenas herdado do Projeto
  pai não poderão administrar Sistemas clientes ou credenciais.
- Desativação e exclusão de Sistemas clientes permanecerão fora do escopo da
  primeira versão. A interrupção de uma integração será feita pela revogação
  individual de suas Chaves de API ativas.
- Um Sistema cliente sem Chaves de API válidas permanecerá como identidade
  histórica de suas Solicitações, mas não conseguirá autenticar na API.
- A exclusão lógica do Projeto revogará todas as Chaves de API ativas de seus
  Sistemas clientes. Essa revogação em lote será uma regra de segurança do
  ciclo de exclusão do Projeto, não uma operação geral de desativação.
- Sistemas clientes e Solicitações permanecerão vinculados ao Projeto excluído
  para preservar o histórico. Se o Projeto for restaurado, as credenciais
  revogadas não voltarão a funcionar e novas chaves precisarão ser emitidas.

## Opções consideradas

- **Usar o Projeto como owner direto:** rejeitada porque não forneceria uma
  identidade estável nem isolamento entre vários Sistemas clientes do mesmo
  Projeto.
- **Usar o registro exato da Chave de API como identidade:** rejeitada porque a
  renovação cria outra chave e separaria artificialmente o histórico da mesma
  integração.
- **Usar o nome da chave:** rejeitada porque o valor é editável e não possui
  unicidade.

## Consequências

A integração exigirá uma entidade e uma interface de gerenciamento para Sistemas clientes. As abilities serão resolvidas pelo Sistema cliente, enquanto a autorização de cada rota também verificará seu vínculo com o Projeto da URL. Essa estrutura adiciona persistência, mas mantém histórico e isolamento durante renovações de credenciais.

A primeira versão não oferecerá uma operação única para suspender todas as
credenciais. Se um Sistema cliente possuir várias chaves, elas precisarão ser
revogadas individualmente; essa limitação é operacional e poderá ser revista
se o volume de credenciais justificar uma suspensão em lote.
