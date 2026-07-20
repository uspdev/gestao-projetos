# 01 — Centralizar a renderização Markdown segura

**O que construir:** substituir a renderização global atual por um serviço único, testável e seguro, aplicar a política aprovada a todos os consumidores e converter o HTML legado conhecido de Tipo de projeto para Markdown.

**Blocked by:** Nenhum — pode iniciar imediatamente.

**Status:** ready-for-agent

- [ ] Registrar `MarkdownRenderer` como serviço injetável de instância única e configurá-lo com GitHub Flavored Markdown, `html_input: escape`, URLs inseguras bloqueadas e aninhamento máximo de 20 níveis.
- [ ] Implementar na árvore de sintaxe abstrata a lista permitida de URLs relativas, âncoras, HTTP e HTTPS; bloquear os demais esquemas.
- [ ] Fazer todos os links internos e externos abrirem em nova aba com `noopener noreferrer`.
- [ ] Degradar toda sintaxe de imagem Markdown para link seguro, sem emitir `<img>` nesta versão.
- [ ] Emitir classes de linguagem em blocos de código sem realce HTML no servidor.
- [ ] Migrar o componente `markdown-content` e os usos diretos da área administrativa para o novo serviço.
- [ ] Remover o uso de `md2html()` na aplicação, a implementação local ineficaz e as dependências abandonadas de realce no servidor, sem alterar `text2html()`.
- [ ] Centralizar o estilo do conteúdo Markdown em `resources/css/app.css`, sem `<style>` por conteúdo.
- [ ] Tornar as Anotações prévias da reunião Markdown e manter Ata e Transcrição como texto simples.
- [ ] Aplicar a renderização segura às descrições de Tipo de projeto e converter de forma protegida e idempotente o registro HTML legado conhecido para Markdown.
- [ ] Cobrir o serviço com testes unitários de HTML, XSS, URLs, imagens, links, aninhamento, blocos de código e conteúdo vazio.
- [ ] Cobrir por testes HTTP os campos consumidores e a preservação de Ata, Transcrição e e-mails.

## Critérios de conclusão

- Nenhum consumidor da aplicação chama `md2html()`.
- Entradas como HTML com manipuladores, `javascript:`, `data:` e imagens externas não produzem conteúdo ativo.
- O mesmo Markdown gera o mesmo HTML seguro em todos os campos suportados.
- A conversão legada preserva o significado do texto e pode ser implantada sem truncar ou reinterpretar outros registros.
