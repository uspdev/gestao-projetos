# Divisores e sombras dos cards

## Resumo

Manter a interface neutra e usar as cores da [EESC-USP](https://eesc.usp.br/institucional/identidade_visual.php) somente em divisores compactos abaixo dos títulos dos cards. Não aplicar superfícies coloridas aos cards ou aos seus cabeçalhos.

| Uso | Cor do divisor |
| --- | ---: |
| Projeto | `#234983` (azul EESC) |
| Tarefa | `#718596` (cinza-azulado) |
| Reunião | `#47708D` (azul-aço) |
| Conteúdo | `#C8D9E8` |
| Informações e recursos | `#D4DEE5` |
| Arquivos, Comentários e Menções | `#6F8799` |

O dourado suave `#D5A13A` é usado como divisor inferior compacto no cabeçalho do módulo de Reuniões. O card principal do Projeto não recebe divisor; os cards internos e os subcards mantêm seus divisores.

Arquivos, Comentários e Menções compartilham o divisor azul-acinzentado `#6F8799`. A sombra compartilhada `0 0.25rem 0.75rem rgba(31, 54, 73, 0.12)` permanece aplicada aos cards.

## Implementação

- Manter todo o CSS em arquivos Blade, sem criar folhas `.css` e sem qualquer etapa de compilação.
- Definir os tokens e as classes compartilhadas dentro da seção de estilos do layout Blade principal.
- Manter estilos exclusivos, como animações e dimensões, nos próprios componentes usando `@pushOnce('styles')`.
- Aplicar divisores inferiores de `1px` aos cabeçalhos de conteúdo, informações, recursos e aos cabeçalhos de entidade de Tarefa e Reunião.
- Manter a borda lateral dos cards de subprojeto, mas removê-la do card principal de Projeto usado em listagens e dashboards.
- Aplicar a sombra compartilhada `0 0.25rem 0.75rem rgba(31, 54, 73, 0.12)` aos cards, preservando sombras de interação mais fortes em estados de hover quando existirem.
- Remover os fundos coloridos, as superfícies da dashboard e as classes de configuração criadas exclusivamente para colorir cards.
- Manter brancos e neutros os conteúdos internos de descrição, membros, arquivos, comentários, pautas e configurações.

## Testes

- Verificar por testes de renderização a presença dos divisores e da sombra compartilhada.
- Confirmar visualmente em desktop e mobile que o card principal do Projeto não possui divisor e que os cabeçalhos dos subcards continuam com seus divisores.
- Verificar hover, foco, legibilidade e telas que exibam simultaneamente projeto e seus módulos.
- Confirmar que badges de status, prioridade e atraso continuam funcionando independentemente da nova identidade visual.

## Premissas

- As cores identificam o tipo da entidade apenas nos divisores, não em seu estado ou superfície.
- Cards agregadores e painéis internos permanecem neutros. Em listas homogêneas, como o Kanban de tarefas, o status e os badges continuam fornecendo a sinalização necessária.
- Não haverá alterações em banco, modelos, rotas, APIs ou publicação de ativos.
