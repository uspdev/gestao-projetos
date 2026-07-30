# 02 — Integrar editor e pré-visualização oficial

**O que construir:** oferecer EasyMDE com perfis completo e compacto nos campos Markdown e uma pré-visualização autenticada produzida pelo `MarkdownRenderer` oficial.

**Blocked by:** 01 — Centralizar a renderização Markdown segura.

**Status:** ready-for-agent

- [ ] Carregar globalmente EasyMDE 2.20.0 e o bundle comum do `highlight.js` 11.11.1 pelo jsDelivr, antes dos ativos próprios, com versões fixas, SRI SHA-384 e `crossorigin="anonymous"`.
- [ ] Remover EasyMDE e `highlight.js` das dependências npm; preservar Laravel Mix, Webpack e PostCSS apenas para compilar os JavaScripts e estilos próprios mantidos em `resources/`.
- [ ] Remover Lodash e seu carregamento por `bootstrap.js`, sem substituição por CDN, pois não existe consumidor na aplicação.
- [ ] Manter o `textarea`, o salvamento e as funções locais disponíveis quando EasyMDE ou `highlight.js` não carregarem, sem fallback local, npm ou por outro CDN.
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
- [ ] Cobrir por teste HTTP as URLs e versões do jsDelivr, SRI, `crossorigin` e ordem de carregamento.
- [ ] Cobrir os dois perfis, a pré-visualização, respostas fora de ordem, formulários em modal/colapso e degradação sem os objetos globais com Dusk usando os ativos reais do jsDelivr no fluxo normal; documentar acesso à internet como requisito da suíte.

## Critérios de conclusão

- Projeto, Tarefa, Comentário, Anotações prévias da Reunião e do Item usam o perfil correto.
- O HTML visto na pré-visualização é produzido pelo mesmo serviço da exibição salva.
- O bundle comum realça as linguagens documentadas e blocos de linguagens desconhecidas permanecem legíveis sem realce.
- Uma indisponibilidade do jsDelivr degrada o editor para `textarea` sem impedir o salvamento.
- Ata, Transcrição e e-mails permanecem fora do editor e sem mudança de comportamento.
