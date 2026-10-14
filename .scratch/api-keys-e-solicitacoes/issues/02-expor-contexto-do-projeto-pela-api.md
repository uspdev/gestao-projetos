# 02 — Expor o contexto do Projeto pela API

**O que construir:** permitir que um Sistema cliente autenticado leia o contexto geral do seu Projeto por uma rota JSON previsível, sem acessar outro Projeto nem expor configurações humanas internas.

**Blocked by:** 01 — Instalar API Keys e administrar Sistemas clientes.

**Status:** ready-for-agent

- [ ] Expor a leitura do Projeto sob o prefixo `/api`, sem segmento de versão, identificando-o explicitamente pelo slug.
- [ ] Exigir a ability `projects.read` pelo middleware do package e aceitar a credencial somente pelo cabeçalho Bearer.
- [ ] Confirmar que o owner autenticado é um Sistema cliente pertencente exatamente ao Projeto da URL e responder como recurso não encontrado em qualquer divergência.
- [ ] Retornar recurso JSON explícito com ID, slug, nome, descrição Markdown original, estado com valor e rótulo, tipo, fase, Projeto pai resumido, tags, módulos habilitados, URL web, criação e atualização.
- [ ] Não expor membros, papéis humanos, visibilidade, herança de permissões nem campos de autoria e auditoria.
- [ ] Informar no recurso se o módulo de Tarefas está habilitado, sem impedir a leitura do Projeto quando estiver desabilitado.
- [ ] Preservar os erros `401` e `403` da biblioteca, o formato `404` da aplicação e o limite atual de 60 requisições por minuto por IP.
- [ ] Tornar o aviso de alteração do slug explícito sobre a quebra das URLs da API e destacá-lo quando o Projeto possuir Sistemas clientes, sem confirmação adicional ou alias antigo.
- [ ] Cobrir autenticação, abilities, escopo, conteúdo, ausência de campos internos, módulo e aviso por testes de funcionalidade HTTP.
