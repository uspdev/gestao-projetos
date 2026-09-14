# Papéis e abilities das Chaves de API

**Status:** aceito

As Chaves de API usarão os papéis `viewer` e `contributor`, alinhados ao vocabulário do Projeto, mas com autorização própria e explícita. Os papéis não representarão membros humanos nem herdarão automaticamente as regras de `ProjectUserRole`.

## Contexto

O package `uspdev/api-keys` persiste o papel como string e delega ao owner a resolução de abilities. Seus valores padrão incluem `collaborator` e `administrator`, mas a aplicação hospedeira define o vocabulário e o significado efetivos. Neste domínio, `contributor` já é o termo adotado para contribuição em Projetos.

## Decisão

- O Sistema cliente resolverá as abilities das suas Chaves de API.
- O papel `viewer` concederá `projects.read`, `tasks.read` e
  `requests.read`.
- O papel `contributor` concederá `projects.read`, `tasks.read`,
  `requests.read` e `requests.create`.
- Um papel desconhecido não concederá nenhuma ability.
- A primeira versão não oferecerá o papel `administrator`.
- Nenhum papel usará o wildcard `*`; novas abilities exigirão concessão
  explícita.
- O `contributor` enviará Solicitações, mas não criará Tarefas diretamente.
- A interface de emissão oferecerá as finalidades `integration`, com o rótulo
  “Integração”, e `ai`, com o rótulo “IA”, seguindo o exemplo do package.
- A finalidade será somente um metadado descritivo da Chave de API. Ela não
  concederá abilities, não selecionará rotas e não alterará o formato das
  respostas; essas regras continuarão determinadas pelo papel e pelo contrato
  da rota.
- As rotas de negócio aceitarão a credencial exclusivamente pelo cabeçalho
  `Authorization: Bearer <chave>`.
- O fallback de chave por parâmetro da URL oferecido pelo package permanecerá
  desabilitado. Os consumidores previstos deverão enviar cabeçalhos HTTP.
- A primeira versão manterá o limitador já aplicado ao grupo `api`: 60
  requisições por minuto por endereço IP, com resposta `429 Too Many Requests`
  quando excedido.
- Não será criado inicialmente um limite específico por Chave de API. Sistemas
  clientes atrás do mesmo endereço IP compartilharão a cota; essa limitação
  operacional poderá motivar uma evolução futura.
- A expiração da Chave de API permanecerá opcional, conforme o componente do
  package. Quando informada, deverá estar no futuro; sem vencimento, a chave
  continuará válida até ser revogada.
- A renovação manterá o comportamento do package: uma nova credencial será
  criada e a anterior será revogada, sem reativação posterior.

## Opções consideradas

- **Usar `collaborator`:** rejeitada porque pertence ao vocabulário padrão do
  package e diverge do termo `contributor` já adotado pela aplicação.
- **Reutilizar automaticamente `ProjectUserRole`:** rejeitada porque uma Chave
  de API não representa um usuário membro e não participa de herança ou vínculo
  local de permissões.
- **Oferecer `administrator` com `*`:** rejeitada porque concederia acesso
  automático a rotas futuras ainda não avaliadas.

## Consequências

Os nomes permanecem familiares, mas as permissões da integração poderão evoluir sem alterar silenciosamente os papéis dos membros humanos. Toda nova operação da API deverá declarar sua ability e ser adicionada deliberadamente aos papéis elegíveis.

As finalidades permitirão distinguir credenciais de integrações convencionais
e de consumidores baseados em IA para identificação e auditoria, sem criar
dois comportamentos de API.
