# Sistema cliente como único ator externo

**Status:** aceito

O Sistema cliente será o único ator externo reconhecido pela API. Solicitações, consultas e Respostas à Solicitação serão tratadas no nível do sistema, sem representar, autenticar ou filtrar usuários individuais do cliente.

## Contexto

A Chave de API autentica o Sistema cliente, e o Gestão de Projetos não precisa conhecer a individualidade dos usuários dos sistemas integrados para receber, avaliar e responder Solicitações. Introduzir uma `requester_reference` acrescentaria correlação sem criar uma fronteira real de autorização individual.

## Decisão

- O Sistema cliente será o único principal autenticado pela API.
- A primeira versão não receberá nem armazenará `requester_reference`.
- Uma Chave de API poderá consultar todas as Solicitações do seu Sistema
  cliente dentro do Projeto indicado na URL.
- A Resposta à Solicitação será dirigida ao Sistema cliente.
- Identidade, autorização e apresentação por usuário dentro do sistema
  integrado permanecerão fora do escopo.

## Opções consideradas

- **Armazenar uma referência do usuário externo:** rejeitada por não ser
  necessária ao fluxo atual e por não fornecer autorização individual sem um
  mecanismo adicional de autenticação.
- **Federar a identidade dos usuários externos:** rejeitada porque ampliaria o
  escopo para OAuth, JWT ou tecnologia equivalente sem demanda presente.

## Consequências

O contrato da API e o modelo de Solicitações ficam menores, e cada Sistema cliente enxerga seu próprio conjunto completo de Solicitações. Caso a autoria ou a separação por usuário se torne necessária no futuro, ela exigirá uma nova decisão de identidade e autorização, não apenas a inclusão de um filtro recebido do cliente.
