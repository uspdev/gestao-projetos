# Nomenclatura técnica de Integrações

**Status:** aceito

As entidades Sistema cliente e Solicitação terão nomes técnicos explícitos em
inglês, preservando o vocabulário em português na interface e na documentação
de domínio.

## Decisão

- Sistema cliente será implementado pelo model `ClientSystem`, persistido na
  tabela `client_systems`.
- O alias estável do owner na configuração de `uspdev/api-keys` será
  `client-system`.
- Solicitação será implementada pelo model `ProjectRequest`, persistido na
  tabela `project_requests`.
- Seus estados serão representados por `ProjectRequestStatus`, com os valores
  `pending`, `accepted` e `rejected`.
- As rotas externas continuarão usando o segmento `/requests`, sem expor os
  nomes internos de classes ou tabelas.

## Contexto

Uma classe denominada somente `Request` conflitaria nos imports e na leitura
do código com `Illuminate\Http\Request`, usado amplamente pelos controllers e
middlewares. `ProjectRequest` mantém o significado de domínio e evita aliases
recorrentes.

## Consequências

O código terá nomes inequívocos, enquanto os textos destinados aos usuários
continuarão usando Sistema cliente e Solicitação conforme o glossário do
projeto.
