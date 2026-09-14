# Identidade de Solicitantes delegada aos Sistemas clientes

**Status:** substituído pelo ADR 0013

> **Revisão posterior:** o ADR 0013 remove a identidade individual do
> Solicitante externo e torna o Sistema cliente o único ator externo desta
> integração. A `requester_reference` não fará parte da primeira versão.

O Gestão de Projetos autenticará o Sistema cliente pela Chave de API, mas delegará a ele a autenticação dos seus usuários. Cada Solicitação receberá uma `requester_reference` estável e opaca, válida somente dentro do Sistema cliente e usada para correlação, não como credencial.

## Contexto

Os usuários que enviam Solicitações pertencem a sistemas como Chamados ou Equivalência e podem não existir como Usuários no Gestão de Projetos. A mesma referência local pode identificar pessoas diferentes em Sistemas clientes distintos, e a API não possui informação suficiente para verificar se quem apresentou uma referência é realmente aquela pessoa.

## Decisão

- A Chave de API autenticará somente o Sistema cliente.
- O Sistema cliente manterá sua Chave de API no backend e não a entregará ao
  navegador ou ao usuário final.
- O Sistema cliente será responsável por autenticar o Solicitante externo e
  enviar sua `requester_reference` ao criar a Solicitação.
- A `requester_reference` será interpretada dentro do escopo do Sistema
  cliente; não terá unicidade global.
- O Gestão de Projetos poderá usar essa referência para armazenar, consultar e
  filtrar Solicitações, mas não como prova de identidade ou autorização.
- O Sistema cliente será responsável por apresentar a cada usuário somente as
  Solicitações que ele pode visualizar.

## Opções consideradas

- **Autenticar cada Solicitante no Gestão de Projetos:** rejeitada nesta
  entrega porque exigiria federação de identidade por OAuth, JWT ou mecanismo
  equivalente.
- **Emitir uma Chave de API por usuário externo:** rejeitada por misturar a
  identidade do Sistema cliente com a de seus usuários e ampliar
  desnecessariamente o ciclo de gestão de credenciais.
- **Confiar na referência como autorização:** rejeitada porque qualquer
  portador da chave do Sistema cliente poderia informar outra referência.

## Consequências

O Gestão de Projetos conhecerá a origem declarada de cada Solicitação, mas a fronteira de segurança individual permanecerá no Sistema cliente. Bibliotecas e interfaces clientes deverão encaminhar as ações pelo próprio backend e nunca expor a Chave de API ao navegador.
