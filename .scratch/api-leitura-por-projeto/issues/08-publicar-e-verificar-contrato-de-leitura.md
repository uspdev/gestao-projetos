# 08 — Publicar e verificar o contrato de leitura

**O que construir:** oferecer documentação e verificação integrada suficientes para que outro sistema ou modelo LLM consuma a API somente de leitura sem depender de conhecimento do código.

**Blocked by:** 02 — Leitura filtrada de Tarefas; 03 — Leitura de Reuniões; 05 — Download de Arquivos; 07 — Retirar Sistemas clientes.

**Status:** ready-for-agent

- [ ] O guia da API documenta as sete rotas `GET`, abilities, Bearer, papéis, escopo por Projeto, representações, filtros, paginação, ordenação, módulos desativados, erros `401`/`403`/`404`/`409`/`422`/`429` e exemplos de consumo.
- [ ] A documentação justifica explicitamente que a abertura e a triagem de demandas pertencem ao Chamados e que o Gestão de Projetos não recebe Solicitações nem cria Tarefas pela API.
- [ ] O guia explica que alterar o slug quebra as URLs sem redirecionamento, como atualizar consumidores e que o download exige Bearer; não descreve Swagger, endpoints próprios de Links nem endpoints de escrita.
- [ ] O teste manual exercita, com uma Chave de Projeto, leitura do Projeto, listagens, detalhes e download, incluindo filtros e uma resposta de erro representativa.
- [ ] Os testes de contrato documental e a suíte de integração são atualizados para os nomes canônicos e o contrato vigente, sem expectativas de Sistemas clientes ou Solicitações.
- [ ] A verificação final percorre emissão e revogação da chave, isolamento, módulos desligados, paginação, conteúdo integral e download, preservando os fluxos web não relacionados.
