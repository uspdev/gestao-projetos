# Estratégia de testes da integração por API

**Status:** aceito

A entrega será verificada por testes de integração do Gestão de Projetos com o
contrato público de `uspdev/api-keys`, sem duplicar a suíte interna do package.

## Decisão

Os testes da aplicação cobrirão:

- instalação, configuração, owner e resolução de abilities;
- credenciais ausentes, inválidas, expiradas e revogadas;
- a matriz dos papéis `viewer` e `contributor`;
- isolamento entre Projetos e entre Sistemas clientes;
- paginação, filtros e conteúdo integral das representações;
- validação e criação de Solicitações;
- comportamento com o módulo de Tarefas desabilitado;
- permissões das páginas administrativas e da fila de Solicitações;
- aceitação, rejeição e autoria da avaliação;
- cancelamento ou erro de validação mantendo a Solicitação pendente;
- repetição ou concorrência na aceitação sem criar mais de uma Tarefa;
- exclusão do Projeto revogando suas Chaves de API;
- aviso sobre a quebra de URLs da API ao alterar o `slug`.

Não serão repetidos testes de detalhes internos do package, como geração de
segredo, hashing ou implementação própria da autenticação. A aplicação testará
esses comportamentos somente pelos pontos públicos necessários para comprovar
que a integração está correta.

## Consequências

A suíte se concentra nos riscos adicionados ao domínio e nos limites entre a
aplicação e a biblioteca. Falhas internas da biblioteca continuam sendo
responsabilidade da suíte do próprio package, enquanto incompatibilidades de
configuração ou uso serão detectadas neste repositório.
