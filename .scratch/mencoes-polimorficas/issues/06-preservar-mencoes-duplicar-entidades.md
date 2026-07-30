# 06 — Preservar Menções ao duplicar entidades

**O que construir:** fazer a duplicação de Projeto, Tarefa e Reunião preservar o Markdown e suas Menções como relações históricas, sincronizadas antes do commit e somente depois de o novo contexto possuir seus vínculos. A cópia não pode alterar membros, acessos, compartilhamentos ou destinos em razão das Menções.

**Bloqueado por:** 02 — Mencionar Projetos com busca e navegação autorizadas; 03 — Mencionar Tarefas com prioridade contextual; 04 — Mencionar Reuniões com rota contextual; 05 — Mencionar e compartilhar Arquivos pela sintaxe unificada.

**Status:** ready-for-agent

- [ ] Copiar os campos Markdown atuais sem remover, converter ou reescrever suas Menções.
- [ ] Adiar a sincronização da cópia até que membros, Projetos e demais vínculos do novo contexto estejam persistidos.
- [ ] Executar criação, vinculação e sincronização ainda na mesma transação, sem janela observável de inconsistência.
- [ ] Tratar Menções copiadas como históricas importadas, sem exigir que satisfaçam novamente as regras de nova inserção.
- [ ] Preservar Menções a usuários quando um Projeto for duplicado sem copiar seus membros.
- [ ] Não adicionar usuários ao novo Projeto em razão de Menções copiadas.
- [ ] Não compartilhar, transferir ou duplicar Arquivos em razão de Menções copiadas.
- [ ] Não conceder acesso a Projeto, Tarefa, Reunião ou Arquivo mencionado.
- [ ] Manter as chaves dos destinos originais, inclusive quando Tarefas ou Reuniões também forem duplicadas.
- [ ] Não criar remapeamento automático entre entidades originais e suas cópias.
- [ ] Aplicar autorização individual na apresentação da cópia, exibindo link para o destino original ou a mensagem de falta de permissão.
- [ ] Preservar destino não encontrado quando a cópia apontar para uma entidade posteriormente excluída.
- [ ] Reverter toda a duplicação se a sincronização do índice falhar antes do commit.
- [ ] Cobrir Projeto com e sem cópia de membros, com e sem Tarefas/Reuniões, além de duplicações isoladas de Tarefa e Reunião.
- [ ] Comprovar que o índice da fonte original não é alterado e que a nova fonte recebe relações próprias para os mesmos destinos.
