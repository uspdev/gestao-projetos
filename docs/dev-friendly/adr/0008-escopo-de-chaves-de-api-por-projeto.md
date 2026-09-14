# Escopo de Chaves de API por Projeto

**Status:** substituído parcialmente pelo ADR 0011

> **Revisão posterior:** o ADR 0011 substitui a decisão de tornar o `Project`
> proprietário direto das Chaves de API. O escopo continua limitado a um único
> Projeto e as rotas continuam identificando-o explicitamente pelo `slug`, mas
> o proprietário estável das chaves passa a ser o Sistema cliente vinculado ao
> Projeto.

As Chaves de API usadas pelas integrações automatizadas serão limitadas a um
único Projeto. As rotas da API identificarão explicitamente esse Projeto pelo
seu `slug`, e cada requisição deverá confirmar que ele é exatamente o Projeto
ao qual pertence o Sistema cliente proprietário da chave.

## Contexto

O package `uspdev/api-keys` delega ao model proprietário a resolução das abilities, mas não impede sozinho que uma chave válida seja usada contra outro recurso. O sistema já organiza visualização e criação de Tarefas por Projeto, e permitir uma chave global aumentaria o impacto de uma credencial exposta.

## Decisão

- Conforme o ADR 0011, o Sistema cliente será o proprietário direto das Chaves
  de API e pertencerá a um único Projeto.
- O `slug` será o identificador do Projeto nas URLs da API.
- A alteração do `slug` será considerada uma quebra deliberada das URLs
  externas da API; a chave e o vínculo com o Projeto continuarão válidos.
- Uma Chave de API não poderá ler ou criar recursos em outro Projeto, mesmo que seu papel possua outras abilities.
- A autorização da ability e a conferência de propriedade serão controles distintos e obrigatórios.
- O fluxo de alteração do `slug` e a documentação da integração deverão
  comunicar explicitamente essa consequência ao usuário.
- O formulário exibirá de forma visível que a alteração quebrará links antigos,
  inclusive URLs da API usadas por Sistemas clientes, e que os consumidores
  precisarão ser atualizados manualmente. Quando o Projeto possuir algum
  Sistema cliente, o aviso receberá maior destaque.
- A primeira versão não exigirá uma confirmação adicional e não manterá
  redirecionamentos nem aliases de slugs anteriores.

## Opções consideradas

- **Chave pertencente ao Usuário:** rejeitada porque ampliaria o alcance da credencial para vários Projetos e exigiria adaptar as Policies atuais, que trabalham com o Usuário autenticado.
- **Chave global com lista de Projetos:** rejeitada nesta entrega porque exigiria uma nova entidade de integração, escopos persistidos e uma superfície administrativa adicional.
- **Projeto implícito na URL:** rejeitada porque tornaria cada chave menos explícita e impediria o uso claro de uma mesma integração em diferentes Projetos.

## Consequências

Cada integração será representada por um Sistema cliente por Projeto e poderá
receber uma ou mais Chaves de API ao longo do tempo. O vazamento de uma chave
ficará limitado ao Projeto ao qual esse sistema pertence. Alterações de slug
deverão ser refletidas pelos consumidores da API, sem alterar a identidade do
Sistema cliente nem o proprietário da chave. Uma integração que usar a URL
antiga receberá falha de resolução do Projeto até ser atualizada, e essa
limitação será apresentada de forma explícita antes ou durante a alteração do
slug.
