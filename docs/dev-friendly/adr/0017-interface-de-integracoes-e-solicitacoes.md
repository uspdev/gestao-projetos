# Interface de Integrações e Solicitações

**Status:** aceito

A administração de Sistemas clientes e Chaves de API ficará nas configurações
do Projeto, enquanto a avaliação de Solicitações ficará junto ao módulo de
Tarefas.

## Contexto

Sistemas clientes e suas credenciais são configurações administrativas do
Projeto. Solicitações, por outro lado, constituem uma fila de propostas que os
responsáveis pelo trabalho precisam avaliar antes de transformá-las em
Tarefas. O package fornece um componente de gerenciamento de chaves, mas não
fornece página GET, menu ou layout para a aplicação hospedeira.

## Decisão

- As Configurações do Projeto terão uma seção “Integrações” com a listagem dos
  Sistemas clientes e ações para criar ou editar seus dados.
- Cada Sistema cliente oferecerá uma ação “Gerenciar chaves” que abrirá uma
  página própria.
- A página de chaves renderizará diretamente o componente de gerenciamento do
  package `uspdev/api-keys`, reutilizando suas operações de emissão, renovação
  e revogação em vez de recriá-las na aplicação.
- A área de Tarefas terá uma entrada “Solicitações” que abrirá a fila de
  avaliação.
- A quantidade de Solicitações pendentes aparecerá junto ao título dessa
  entrada, à esquerda, conforme a diretriz visual do projeto.
- A tela de Solicitações oferecerá listagem, consulta, aceitação e rejeição de
  acordo com a autorização do usuário local.
- A visualização e a avaliação da fila serão permitidas somente para
  administradores e contribuidores com vínculo local no Projeto.
- Visualizadores, pessoas com acesso herdado e administradores globais sem
  vínculo local não verão a fila. O acesso administrativo global a Sistemas
  clientes e chaves não concederá acesso ao conteúdo das Solicitações.

## Consequências

Credenciais permanecem junto das demais configurações administrativas, e a
triagem fica próxima do trabalho que poderá gerar. A aplicação precisará criar
as páginas e a navegação, mas delegará o ciclo das credenciais ao componente
mantido pelo package.
