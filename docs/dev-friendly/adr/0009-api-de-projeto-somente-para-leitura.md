# API de Projeto somente para leitura

**Status:** aceito

O Projeto será o Projeto proprietário direto das suas Chaves de API e a API
de negócio autenticada por essas credenciais oferecerá somente operações de
leitura. A
entrada e a triagem de correções, melhorias e outras demandas pertencem ao
sistema Chamados, que já oferece abertura, acompanhamento, comentários e
arquivos para demandas no contexto USP. Repetir esse fluxo no Gestão de
Projetos criaria dois ciclos concorrentes para a mesma finalidade.

Não haverá identidade intermediária de Sistema cliente nem recebimento de
Solicitações nesta API. Cada chave pertencerá diretamente ao Projeto cujos
recursos pode consultar. A interface web autenticada usará as mutações
fornecidas pela biblioteca para emitir, renovar e revogar credenciais; a
restrição a `GET` se aplica à API de negócio, não ao gerenciamento
administrativo das chaves nem às operações web da aplicação.

Os papéis disponíveis serão `viewer` e `contributor`. Sua distinção será
confirmada posteriormente com a supervisão; a equivalência inicial das
abilities de leitura não constitui uma definição definitiva desse vocabulário.

Enquanto a API permanecer somente para leitura, ambos os papéis concederão as
mesmas abilities: `projects.read`, `meetings.read`, `tasks.read` e
`files.read`. Nenhum papel concederá wildcard `*`, e papéis desconhecidos não
concederão abilities. A equivalência temporária preserva a role registrada em
cada chave e permite revisar sua semântica posteriormente sem alterar os
endpoints.

As chaves serão enviadas exclusivamente em `Authorization: Bearer`; o fallback
por parâmetro da URL permanecerá desabilitado. As finalidades `integration` e
`ai` oferecidas na emissão serão apenas metadados descritivos, sem efeito nas
abilities ou nas respostas.

## Consequências

- Não haverá modelos, tabelas ou fluxos de API e interface para Sistemas
  clientes ou Solicitações no Gestão de Projetos.
- O Projeto resolve diretamente os papéis e as abilities das suas Chaves de
  API.
- As configurações do Projeto apresentarão um único card de Chaves de API com
  o gerenciador fornecido pelo package e o próprio Projeto como proprietário.
  Não haverá telas intermediárias de Sistemas clientes. Somente administradores
  diretamente vinculados ao Projeto poderão emitir, renovar ou revogar essas
  credenciais; contribuidores, visualizadores, administradores herdados e
  administradores globais não vinculados permanecerão sem acesso.
- A exclusão lógica do Projeto revogará imediatamente todas as suas Chaves de
  API ativas. Uma restauração não reativará credenciais antigas; novas chaves
  deverão ser emitidas por um administrador autorizado.
- O recurso público de Arquivos usará o segmento `files`; `media` permanecerá
  apenas como nomenclatura técnica da biblioteca e da persistência.
- Todas as rotas de negócio permanecerão aninhadas em
  `/api/projects/{project}`, com o Projeto identificado explicitamente pelo
  `slug`. A aplicação verificará separadamente a ability da chave e se o
  recurso solicitado pertence ou está vinculado ao Projeto proprietário da
  credencial.
- As rotas não terão segmento de versão inicial. Uma mudança incompatível
  futura exigirá versionamento ou migração explícita dos consumidores, sem
  alterar silenciosamente o contrato publicado.
- Quando houver ao menos uma Chave de API ativa, o formulário de alteração do
  slug exibirá um alerta de que todas as URLs da API deixarão de funcionar e
  precisarão ser atualizadas nos consumidores. A alteração continuará
  permitida, sem alias nem redirecionamento para o slug anterior e sem uma
  confirmação adicional além do alerta. A mesma quebra será explicitada na
  documentação da API.
