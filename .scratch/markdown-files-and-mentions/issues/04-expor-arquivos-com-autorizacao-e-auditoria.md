# 04 — Expor Arquivos com autorização, cards e auditoria

**O que construir:** disponibilizar upload, listagem, metadados, miniatura, renomeação, download e exclusão por rotas autorizadas e uma lista reutilizável nas telas dos Proprietários.

**Blocked by:** 03 — Criar persistência e processamento de Arquivos.

**Status:** ready-for-agent

- [ ] Centralizar em política/serviço as regras de visualização, envio e administração para Projeto, Tarefa e Reunião.
- [ ] Permitir leitura a visualizadores e upload a colaboradores, mantendo Tarefa `DONE` bloqueada para mutações e Reunião `COMPLETED` desbloqueada.
- [ ] Permitir renomeação e exclusão ao Autor do arquivo com acesso vigente e ao administrador de Projeto relacionado; manter a sobreposição administrativa global existente.
- [ ] Criar rotas por UUID para metadados, miniatura e download que retornem o mesmo não encontrado para inexistência, falta de acesso ou Proprietário excluído.
- [ ] Entregar originais como `attachment`, com `nosniff` e nome seguro composto do Nome exibido e extensão normalizada; nunca incorporar SVG, HTML, PDF ou outros originais.
- [ ] Criar upload unitário com validação de 100 MB, persistência transacional e geração síncrona da miniatura antes da confirmação e da resposta de sucesso.
- [ ] Criar renomeação apenas do Nome exibido, aceitando duplicatas sem alterar URL, caminho, Nome original ou conteúdo.
- [ ] Criar exclusão definitiva com confirmação explícita e aviso de referências quebradas.
- [ ] Registrar upload, renomeação e exclusão no `activity_log`, sem conteúdo binário; não registrar listagem, metadados, miniatura ou download.
- [ ] Criar lista/card reutilizável e responsivo nas telas de Projeto, Tarefa e Reunião, com campos, estados, ações, ordem decrescente e paginação de 20 itens.
- [ ] Restringir a exibição do Nome original a quem possa administrar o Arquivo.
- [ ] Cobrir todas as operações por Proprietário, papel, autoria, status, exclusão lógica e administrador global em testes HTTP.
- [ ] Cobrir cabeçalhos, nomes maliciosos, UUID, paginação, auditoria e ausência de auditoria de download.
- [ ] Cobrir upload, card, estado de miniatura e download em fluxo Dusk.

## Critérios de conclusão

- Toda entrega física passa pela mesma autorização usada por listagens e seletores.
- A lista nunca revela Arquivo de Proprietário excluído ou inacessível.
- A exclusão individual remove definitivamente original, conversões e metadados.
