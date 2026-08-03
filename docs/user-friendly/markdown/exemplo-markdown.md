# Exemplo completo de descrição de projeto

## Texto digitado

````markdown
# Gestão de Projetos USP

Sistema institucional para organizar **projetos**, tarefas, reuniões e decisões.
O objetivo é oferecer uma visão *confiável* do trabalho, com informações
acessíveis às equipes e preservadas no histórico.

Visão geral do projeto
======================

> Este projeto é a própria evolução do sistema: cada entrega deve melhorar o
> planejamento, a execução e a transparência do trabalho.

## Objetivos

- Centralizar informações de projetos e tarefas.
  - Descrições e decisões em Markdown.
  - Responsáveis, prazos, status e prioridades.
- Organizar reuniões, pautas e itens de pauta.
- Registrar comentários, menções e referências de arquivos.

## Plano de entrega

1. Consolidar a gestão de projetos.
2. Integrar tarefas e responsáveis.
3. Ampliar o módulo de reuniões.
4. Publicar métricas e documentação.

O planejamento inicial foi revisado.\
Agora a equipe acompanha cada entrega no próprio projeto.

### Checklist da próxima versão

- [x] Criar o editor de Markdown.
- [x] Validar a pré-visualização segura.
- [ ] Documentar o fluxo de implantação.
- [ ] Treinar as equipes usuárias.

---

## Indicadores

| Indicador | Situação atual | Meta |
| --- | ---: | ---: |
| Projetos com descrição atualizada | 68% | 95% |
| Tarefas concluídas no prazo | 74% | 90% |
| Reuniões com pauta registrada | 61% | 100% |

## Decisões e referências

O requisito de segurança está **aprovado** e a alternativa ~~descartada~~ não
será implantada. O identificador `MarkdownRenderer` é usado na pré-visualização
e na exibição final.

Consulte o [manual de implantação][manual], veja a [documentação institucional](https://www5.usp.br/)
ou acesse diretamente <https://github.com/uspdev>.

O projeto relacionado é [Módulo de reuniões](/projects/modulo-reunioes), a tarefa
de acompanhamento é [Revisar permissões](/tasks/42) e a reunião de alinhamento é
[Reunião de implantação](/projects/modulo-reunioes/meetings/17).

Responsável pela validação: @[Marina Silva](mention:user:42).
Consulte o @[Programa de modernização](mention:project:18), a
@[Revisar protótipo](mention:task:42), a @[Reunião de kickoff](mention:meeting:17)
e o @[Termo de abertura](mention:file:550e8400-e29b-41d4-a716-446655440000).

Ir para a [seção de indicadores](#indicadores) (o destino só funciona quando a
página fornecer um elemento com esse identificador).

## Código e critérios

Um critério pode ser escrito como `status == 'DONE'`.

```php
if ($project->tasks()->whereNull('completed_at')->exists()) {
    $project->update(['status' => 'IN_PROGRESS']);
}
```

O exemplo de configuração abaixo usa um bloco de código sem linguagem:

```
markdown:
  limite: 10000
  preview: true
```

## Imagens, HTML e caracteres literais

Uma imagem conceitual pode ser indicada com esta sintaxe:

![Fluxo de trabalho do projeto](https://example.test/fluxo-projeto.png)

No Gestão de Projetos, imagens Markdown não são incorporadas: o renderer as
converte em um link seguro com o texto alternativo. Para arquivos reais, envie o
arquivo pela área de arquivos e use a referência inserida pelo seletor.

HTML livre também não é executado:

<span class="destaque">Este elemento será exibido como texto literal.</span>

Para exibir asteriscos sem formatação, use escape: \*este texto não é itálico\*.

## Próximos passos

1. Atualizar o [projeto relacionado](/projects/modulo-reunioes).
2. Criar a tarefa de validação.
3. Registrar a decisão na próxima reunião.

[manual]: /docs/dev-friendly/implantacao-markdown-arquivos-mencoes.md "Guia de implantação"
````

## Pré-visualização esperada

# Gestão de Projetos USP

Sistema institucional para organizar **projetos**, tarefas, reuniões e decisões.
O objetivo é oferecer uma visão *confiável* do trabalho, com informações
acessíveis às equipes e preservadas no histórico.

Visão geral do projeto
======================

> Este projeto é a própria evolução do sistema: cada entrega deve melhorar o
> planejamento, a execução e a transparência do trabalho.

## Objetivos

- Centralizar informações de projetos e tarefas.
  - Descrições e decisões em Markdown.
  - Responsáveis, prazos, status e prioridades.
- Organizar reuniões, pautas e itens de pauta.
- Registrar comentários, menções e referências de arquivos.

## Plano de entrega

1. Consolidar a gestão de projetos.
2. Integrar tarefas e responsáveis.
3. Ampliar o módulo de reuniões.
4. Publicar métricas e documentação.

O planejamento inicial foi revisado.\
Agora a equipe acompanha cada entrega no próprio projeto.

### Checklist da próxima versão

- [x] Criar o editor de Markdown.
- [x] Validar a pré-visualização segura.
- [ ] Documentar o fluxo de implantação.
- [ ] Treinar as equipes usuárias.

---

## Indicadores

| Indicador | Situação atual | Meta |
| --- | ---: | ---: |
| Projetos com descrição atualizada | 68% | 95% |
| Tarefas concluídas no prazo | 74% | 90% |
| Reuniões com pauta registrada | 61% | 100% |

## Decisões e referências

O requisito de segurança está **aprovado** e a alternativa ~~descartada~~ não
será implantada. O identificador `MarkdownRenderer` é usado na pré-visualização
e na exibição final.

Consulte o [manual de implantação](/docs/dev-friendly/implantacao-markdown-arquivos-mencoes.md),
veja a [documentação institucional](https://www5.usp.br/) ou acesse diretamente
<https://github.com/uspdev>.

O projeto relacionado é [Módulo de reuniões](/projects/modulo-reunioes), a tarefa
de acompanhamento é [Revisar permissões](/tasks/42) e a reunião de alinhamento é
[Reunião de implantação](/projects/modulo-reunioes/meetings/17).

Responsável pela validação: @Marina Silva. Consulte @Programa de modernização,
@Revisar protótipo, @Reunião de kickoff e @Termo de abertura.

Ir para a [seção de indicadores](#indicadores).

## Código e critérios

Um critério pode ser escrito como `status == 'DONE'`.

```php
if ($project->tasks()->whereNull('completed_at')->exists()) {
    $project->update(['status' => 'IN_PROGRESS']);
}
```

O exemplo de configuração abaixo usa um bloco de código sem linguagem:

```
markdown:
  limite: 10000
  preview: true
```

## Imagens, HTML e caracteres literais

![Fluxo de trabalho do projeto](https://example.test/fluxo-projeto.png)

O renderer apresenta a imagem acima como o link [Fluxo de trabalho do projeto](https://example.test/fluxo-projeto.png), usando o texto alternativo.

HTML livre também é exibido como texto literal: `<span class="destaque">Este elemento será exibido como texto literal.</span>`.

Para exibir asteriscos sem formatação, use escape: \*este texto não é itálico\*.

## Próximos passos

1. Atualizar o [projeto relacionado](/projects/modulo-reunioes).
2. Criar a tarefa de validação.
3. Registrar a decisão na próxima reunião.
