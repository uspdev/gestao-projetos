# Instruções para agentes

## Controle de commits

Todo agente que trabalhar neste projeto deve respeitar estas regras:

- Não execute `git add`, `git commit`, `git push` nem qualquer outro comando que altere o estado do Git.
- Ao concluir o trabalho, apresente apenas os comandos sugeridos de `git add` e `git commit` para análise do desenvolvedor. Use o padrão conventiona commits e inclua descrição, por exemplo:
    "feat(projects): permitir criação de subprojetos vinculados
    Adiciona a possibilidade de iniciar a criação de um subprojeto pelo
    modal de vínculo, mantendo o projeto pai durante a seleção do tipo e
    no formulário de criação.

    Valida a autorização do usuário, garante que o pai seja um projeto
    organizacional raiz e impede a criação de projetos organizacionais como
    subprojetos.

    Também reorganiza o modal de vínculo, separando as opções de projeto
    existente e projeto novo."
- A decisão de quais arquivos serão incluídos, a revisão da mensagem e a execução dos comandos são responsabilidade exclusiva do desenvolvedor.
- Comandos de leitura, como `git status`, `git diff` e `git log`, podem ser executados quando forem necessários para analisar o trabalho.

## Skills de engenharia

### Rastreador de trabalho

As especificações e os tickets deste repositório usam Markdown local em `.scratch/`. Consulte `docs/agents/issue-tracker.md`.

### Rótulos de triagem

Use os rótulos padrão de triagem, incluindo `ready-for-agent` para trabalhos totalmente especificados. Consulte `docs/agents/triage-labels.md`.

### Documentação de domínio

Este é um repositório de contexto único. Leia `CONTEXT.md` e os ADRs relevantes em `docs/adr/` antes de alterar o domínio. Consulte `docs/agents/domain.md`.

### Glossário de tradução

Quando uma skill usar termos em inglês, consulte `docs/agents/glossario-traducao.md` e use as equivalências em português nos documentos do projeto.
