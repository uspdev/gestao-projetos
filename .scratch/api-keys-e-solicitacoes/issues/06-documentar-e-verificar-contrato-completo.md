# 06 — Documentar e verificar o contrato completo

**O que construir:** entregar aos desenvolvedores de Sistemas clientes um contrato HTTP utilizável e comprovar que os fluxos de leitura, envio e avaliação funcionam em conjunto sem regressões de autorização ou isolamento.

**Blocked by:** 03 — Expor Tarefas integrais pela API; 05 — Avaliar Solicitações e criar Tarefas.

**Status:** ready-for-agent

- [ ] Documentar instalação, requisito PHP 8.3, publicação de recursos, configuração do owner, papéis, finalidades e autenticação Bearer.
- [ ] Documentar todas as rotas com parâmetros, abilities, payloads, campos de resposta, paginação, filtros, ordenação, status HTTP e códigos de erro.
- [ ] Incluir exemplos de consumo para leitura de Projeto, listagem e detalhe de Tarefas e criação, listagem e detalhe de Solicitações.
- [ ] Explicar que `purpose` é metadado, que o Sistema cliente é o único ator externo e que cada credencial permanece limitada a um Projeto.
- [ ] Destacar que alterar o slug quebra URLs existentes, que chaves não são aceitas na query string e que o limite inicial é compartilhado por IP.
- [ ] Documentar o acompanhamento por consulta, a ausência de idempotência e a possibilidade de Solicitações duplicadas após retry.
- [ ] Documentar o comportamento do módulo de Tarefas desabilitado e a separação entre erros `401`, `403`, `404`, `409`, `422` e `429`.
- [ ] Registrar a ordem operacional de instalação e migrations sem executar implantação em produção.
- [ ] Executar a suíte relevante e verificar em conjunto autenticação, matriz de abilities, isolamento, conteúdo integral, estados finais, revogação e regressões dos fluxos existentes.
- [ ] Garantir que a documentação use o vocabulário canônico de Sistema cliente, Solicitação, Avaliador da Solicitação e Projeto de escopo da Chave.
