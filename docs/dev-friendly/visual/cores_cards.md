# Padronizar as cores dos cards por entidade

## Resumo

Aplicar a identidade da [EESC-USP](https://eesc.usp.br/institucional/identidade_visual.php) de maneira discreta. A base institucional é azul e cinza; os subtons abaixo são superfícies derivadas para a interface, não novas cores de marca.

| Uso | Tom de destaque | Superfície clara |
| --- | ---: | ---: |
| Projeto | `#234983` (azul EESC) | `#EEF4FA` |
| Tarefa | `#718596` (cinza-azulado) | `#F4F6F8` |
| Reunião | `#47708D` (azul-aço) | `#EFF4F7` |
| Conteúdo | `#C8D9E8` (divisor) | `#EAF2F9` |
| Informações e recursos | `#D4DEE5` (divisor) | `#F2F5F7` |

O dourado suave `#FFF5DB`, com divisor `#D5A13A`, é reservado exclusivamente ao cabeçalho principal de Reuniões. Ele preserva um ponto quente na interface sem se repetir em cards, listas ou painéis internos.

## Implementação

- Manter todo o CSS em arquivos Blade, sem criar folhas `.css` e sem qualquer etapa de compilação.
- Definir os tokens e as classes compartilhadas dentro da seção de estilos do layout Blade principal.
- Manter estilos exclusivos, como animações e dimensões, nos próprios componentes usando `@pushOnce('styles')`.
- Criar as classes semânticas `entity-card`, `entity-header` e os modificadores `--project`, `--task` e `--meeting`.
- Aplicar uma borda lateral de `3px` somente aos cards de entidade:
    - projetos em listagens, dashboard e prévias de subprojetos;
    - tarefas em grade e dashboard;
    - reuniões em listagens e dashboard.
- Aplicar fundos suaves somente aos cabeçalhos principais, substituindo os `lightCyan` inline.
- Nas páginas de projeto, tarefa e reunião, usar um azul claro nos cards de conteúdo e um cinza claro nos cards de apoio, para distinguir a área de trabalho da área de informações e recursos sem recorrer a novas barras laterais.
- Remover o `DodgerBlue` específico dos projetos organizacionais e a borda de prioridade das tarefas; prioridade, tipo e status continuarão representados pelos badges e textos existentes.
- Manter brancos e neutros os cards internos de descrição, membros, arquivos, comentários, pautas e configurações.

## Testes

- Verificar por testes de renderização a presença do modificador correto em listagens, dashboard, Kanban, subprojetos e páginas de detalhe.
- Confirmar visualmente em desktop e mobile que os cabeçalhos de contexto e cards de entidade mantêm as mesmas cores nas telas aplicáveis.
- Verificar hover, foco, legibilidade e telas que exibam simultaneamente projeto e seus módulos.
- Confirmar que badges de status, prioridade e atraso continuam funcionando independentemente da nova identidade visual.

## Premissas

- As cores identificam o tipo da entidade, não seu estado.
- Os fundos suaves ficam restritos aos cabeçalhos de contexto; textos continuam escuros. Os tons de conteúdo e de informações diferem também pela borda inferior do cabeçalho, para manter a separação sem depender de cores saturadas.
- Cards agregadores e painéis internos permanecem neutros para evitar excesso de cor. Em listas homogêneas, como o Kanban de tarefas, o status e os badges já fornecem a sinalização necessária e não recebem outra faixa de entidade.
- Não haverá alterações em banco, modelos, rotas, APIs ou publicação de ativos.
