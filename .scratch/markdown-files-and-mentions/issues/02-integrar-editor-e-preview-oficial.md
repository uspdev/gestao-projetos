# 02 — Integrar editor e pré-visualização oficial

**O que construir:** oferecer EasyMDE com perfis completo e compacto nos campos Markdown e uma pré-visualização autenticada produzida pelo `MarkdownRenderer` oficial.

**Blocked by:** 01 — Centralizar a renderização Markdown segura.

**Status:** ready-for-agent

- [ ] Instalar EasyMDE e `highlight.js` como dependências npm locais e empacotá-los por Laravel Mix, sem CDN.
- [ ] Criar inicialização reutilizável por atributos de dados, capaz de conviver com formulários inline, modais, erros de validação e múltiplos editores na mesma página.
- [ ] Configurar o perfil completo para descrições e Anotações prévias e o perfil compacto para comentários, conforme a barra definida na especificação.
- [ ] Habilitar o corretor do navegador e desabilitar autosave, tela cheia, lado a lado, imagem externa, upload, colagem e arrastar/soltar Arquivos.
- [ ] Criar endpoint POST de pré-visualização dentro do grupo web autenticado, seguindo autenticação e CSRF existentes sem limite específico de requisições.
- [ ] Validar o limite de 10.000 caracteres e usar o mesmo renderizador e limite de aninhamento da exibição oficial.
- [ ] Atualizar a pré-visualização somente quando visível, após debounce de 500 ms, cancelando ou ignorando respostas obsoletas e mantendo a última resposta válida em falhas.
- [ ] Aplicar `highlight.js` ao HTML oficial no navegador sem alterar o Markdown nem persistir o resultado.
- [ ] Preparar os pontos de extensão dos botões Menção e Referência de arquivo; sua busca e persistência serão conectadas nos tickets posteriores.
- [ ] Preservar valores antigos, mensagens de validação, limites e regras de bloqueio por status de cada formulário.
- [ ] Cobrir endpoint, validação e ausência de persistência/auditoria com testes HTTP.
- [ ] Cobrir os dois perfis, a pré-visualização, respostas fora de ordem e formulários em modal/colapso com Dusk ou teste de navegador equivalente já adotado pelo projeto.

## Critérios de conclusão

- Projeto, Tarefa, Comentário, Anotações prévias da Reunião e do Item usam o perfil correto.
- O HTML visto na pré-visualização é produzido pelo mesmo serviço da exibição salva.
- Ata, Transcrição e e-mails permanecem fora do editor e sem mudança de comportamento.
