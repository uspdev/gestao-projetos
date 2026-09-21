# 07 — Retirar Sistemas clientes

**O que construir:** concluir a propriedade direta das Chaves de API, retirando integralmente a identidade intermediária de Sistema cliente do desenvolvimento não implantado.

**Blocked by:** 01 — Chaves do Projeto e leitura do Projeto; 02 — Leitura filtrada de Tarefas; 06 — Retirar Solicitações.

**Status:** ready-for-agent

- [ ] O Projeto é o único owner configurado para as Chaves de API da aplicação; nenhum alias, policy, modelo, tabela, migração ou tela de Sistema cliente permanece no contrato ativo.
- [ ] A administração das chaves ocorre somente nas configurações do Projeto e respeita o vínculo administrativo direto; não há cadastro nem página intermediária de Sistema cliente.
- [ ] Leitura de Projeto e Tarefas não aceita mais o proprietário antigo. Reuniões e Arquivos, quando presentes, continuam autenticando somente a Chave do Projeto.
- [ ] A exclusão lógica revoga diretamente as Chaves do Projeto e a restauração não as reativa; a mudança de slug continua exibindo o alerta acordado quando houver chaves ativas.
- [ ] Estruturas específicas do proprietário intermediário não implantado são removidas diretamente, sem migração ou cópia de credenciais e sem alterar a persistência fornecida pela biblioteca.
- [ ] Testes HTTP da administração e da API demonstram que apenas o owner Projeto funciona e que não há regressão nos endpoints de leitura já disponíveis.
