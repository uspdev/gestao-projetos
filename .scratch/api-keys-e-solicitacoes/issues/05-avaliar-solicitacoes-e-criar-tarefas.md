# 05 — Avaliar Solicitações e criar Tarefas

**O que construir:** permitir que administradores e contribuidores locais rejeitem uma Solicitação com justificativa ou a aceitem criando uma única Tarefa revisada, preservando a proposta original e a rastreabilidade da decisão.

**Blocked by:** 04 — Receber e acompanhar Solicitações.

**Status:** ready-for-agent

- [ ] Permitir aceitar ou rejeitar somente Solicitações pendentes e somente por administradores ou contribuidores com vínculo local no Projeto.
- [ ] Exigir Resposta à Solicitação textual, não vazia e de até 10.000 caracteres na rejeição; permitir resposta opcional na aceitação.
- [ ] Registrar estado final, avaliador e momento da decisão sem expor a identidade humana pela API externa.
- [ ] Abrir a aceitação no formulário normal de criação de Tarefa com título e descrição previamente preenchidos e conteúdo original preservado.
- [ ] Permitir ao Avaliador ajustar título, descrição, prioridade, datas, tags, responsável e estado sob as validações e autorizações atuais de Tarefa.
- [ ] Manter a Solicitação pendente ao apenas abrir ou cancelar o formulário e diante de qualquer falha de validação.
- [ ] Reutilizar a orquestração existente de criação de Tarefa, incluindo Menções, tags, responsável e efeitos observáveis, sem manter dois fluxos divergentes.
- [ ] Registrar o Avaliador como criador da Tarefa e manter o Sistema cliente como origem da Solicitação.
- [ ] Criar Tarefa, vínculo e avaliação na mesma transação e garantir no máximo uma Tarefa mesmo sob repetição ou concorrência.
- [ ] Tornar aceitação e rejeição estados finais, recusando reabertura, mudança de resultado ou segunda avaliação.
- [ ] Permitir rejeição com o módulo de Tarefas desabilitado, mas bloquear aceitação até sua reativação com uma mensagem clara ao usuário.
- [ ] Fazer a API da Solicitação aceita expor ID, título e URL web da Tarefa resultante e a rejeitada expor sua resposta.
- [ ] Cobrir autorização, estados, resposta, formulário, cancelamento, validação, transação, repetição, concorrência, autoria e representação externa por testes HTTP integrados.
