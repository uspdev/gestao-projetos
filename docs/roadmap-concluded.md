# Roadmap concluído

> **Navegação rápida**
>
> [**Roadmap atual**](roadmap.md)

Este documento registra funcionalidades efetivamente entregues. A descrição é
factual: quando uma implementação atende apenas parte de uma proposta anterior
ou resolve o problema de outra maneira, o limite da entrega é indicado aqui.

## Reuniões, Ata e itens de pauta

**Implementado em:** 05/2026; expansão em 07/2026

O módulo permite criar, editar, consultar e remover reuniões vinculadas a um ou
mais projetos, com data, local e estados de rascunho, agendada, em andamento e
concluída. A Pauta pode conter projetos, tarefas e itens independentes, inclusive
de subprojetos, com ordenação e Anotações prévias próprias.

As reuniões passaram a separar Anotações prévias, Ata e Transcrição. Anotações
prévias e Anotações prévias do item usam Markdown e são bloqueadas durante a
conclusão; a Ata é um registro editável das conclusões e a Transcrição é texto
bruto inserido por ferramenta externa, sem sumarização automática. Itens
independentes têm título próprio, seguem as permissões da reunião e não são
convertidos automaticamente em projetos ou tarefas.

O acesso respeita os módulos habilitados e as permissões de visualização e
contribuição dos projetos. Alterações relevantes são auditadas e podem alimentar
o acompanhamento por e-mail dos usuários que optaram por observar a reunião.

---

## Atualizações de status

**Implementado em:** 05/2026

O acompanhamento de status foi incorporado diretamente aos principais fluxos,
sem uma entidade separada de atualização periódica. Projetos possuem estados de
planejamento, execução, espera, conclusão, cancelamento e arquivamento; tarefas
transitam entre nova, atribuída, em andamento, em revisão, em espera e
concluída; reuniões possuem seu próprio ciclo entre rascunho e conclusão.

As alterações são feitas nas telas dos respectivos recursos e refletem nas
visualizações. Tarefas recebem automaticamente o estado de atribuída ao ganhar
um responsável e registram a data de conclusão. A solução mantém o andamento no
contexto em que o trabalho acontece, em vez de criar relatos paralelos.

---

## Dashboard pessoal do usuário

**Implementado em:** 05/2026

A página inicial autenticada funciona como dashboard individual. Ela reúne os
projetos fixados pelo usuário, as reuniões agendadas dos projetos acessíveis e
as tarefas atribuídas a ele. As tarefas podem ser exibidas em Kanban ou cartões,
pesquisadas e filtradas para mostrar ou ocultar itens concluídos; as preferências
de visualização são mantidas na sessão.

O backend carrega somente tarefas de projetos com o módulo correspondente ativo
e seleciona reuniões de acordo com o acesso do usuário. Ainda não existe um
calendário pessoal ou de projeto; essa é uma pendência separada no roadmap atual.

---

## Estrutura de projetos e subprojetos

**Implementado em:** 06/2026

Projetos podem ser organizacionais, independentes ou vinculados como subprojetos
por meio de uma relação pai e filho. A interface permite localizar, vincular e
desvincular subprojetos, exibi-los na visão geral do projeto organizacional e
navegar entre os níveis. As regras impedem vínculos inválidos e exigem
administração compatível entre os projetos envolvidos.

A estrutura oferece visibilidade e herança configurável de permissões, além de
tipos de projeto que definem fases e módulos disponíveis. Cada projeto mantém
sua própria configuração de módulos, como tarefas e reuniões, respeitando se
eles são obrigatórios ou editáveis para o tipo escolhido. Isso substitui a ideia
de pastas, listas e subtarefas por uma hierarquia alinhada ao domínio do sistema.

---

## Navegação contextual

**Implementado em:** 05/2026

As telas de projetos e tarefas exibem uma trilha contextual simples construída
diretamente nos componentes Blade. Ela oferece retorno à lista principal,
apresenta o projeto organizacional quando o usuário está em um subprojeto e
mantém links para o projeto atual e seus módulos, como tarefas e reuniões.

Os módulos ativos também alimentam o cabeçalho de navegação do projeto. A
solução é baseada na estrutura suportada atualmente, sem gerenciador central,
pacote externo ou montagem recursiva de hierarquias arbitrárias.

---

## Visões e experiências por contexto

**Implementado em:** 05/2026

Em vez de manter interfaces completas e duplicadas para perfis técnicos e
administrativos, o sistema adapta o conteúdo ao contexto. Policies, papéis de
administrador, colaborador e visualizador, visibilidade e herança de permissões
determinam quais recursos e ações cada usuário pode acessar.

Tipos de projeto, fases e ativação granular de módulos permitem criar projetos
mais simples ou mais completos. No módulo de tarefas, o usuário pode alternar
entre tabela e Kanban, mostrar apenas suas tarefas e ocultar ou exibir itens
concluídos. As restrições são aplicadas no backend e na interface.

---

## Trilha de auditoria avançada

**Implementado em:** 06/2026; ampliada em 07/2026

O backend registra criação e alterações dos principais modelos, incluindo
projetos, tarefas, reuniões, itens de pauta, comentários, tipos, módulos, fases,
tags e operações de Arquivos. Os registros identificam o recurso, o evento, o
usuário causador e, quando aplicável, os valores antigos e novos. Relações como
membros, responsáveis, projetos de uma reunião e módulos habilitados também são
auditadas.

O modelo de atividade fornece filtros por categoria, autor, recurso e evento. Há
limpeza programada com políticas de retenção por categoria e suporte a
simulação por `dry-run`. A captura e a consulta estão prontas no backend, mas a
interface gráfica de auditoria por projeto continua pendente.

---

## Markdown, editor e pré-visualização segura

**Implementado em:** 07/2026

Descrições de projetos e tarefas, Anotações prévias de reuniões e itens de pauta
e comentários são armazenados em Markdown e renderizados por um serviço único
com CommonMark e GFM. A pré-visualização usa o mesmo renderizador do conteúdo
salvo. HTML escrito pelo usuário é escapado, esquemas de URL inseguros são
bloqueados e imagens declaradas no Markdown são convertidas em links seguros,
em vez de incorporadas na página.

O editor tem perfis completo e compacto, pré-visualização no servidor e
realce de código no navegador. EasyMDE e `highlight.js` são carregados por CDN
com versões fixas e o formulário continua utilizável como área de texto se os
ativos externos não carregarem. Ata e Transcrição permanecem texto simples.

Essa entrega resolveu a necessidade de texto formatado e links seguros, mas não
implementou incorporação direta de mídias, arrastar ou colar Arquivos no editor.
Essa diferença permanece registrada como pendência no roadmap atual.

Consulte o [guia de Markdown](markdown/README.md) e o
[fluxo de renderização](markdown/diagrama-markdown.md) para o uso detalhado.

---

## Arquivos, Menções a arquivo e compartilhamento com reuniões

**Implementado em:** 07/2026

Foi criado um módulo centralizado de Arquivos para Projetos, Tarefas e
Reuniões. O armazenamento é privado, cada Arquivo possui UUID público estável,
Nome original do arquivo preservado, Nome exibido editável, conteúdo imutável e
limite de 100 MB. Downloads passam por autorização da aplicação; operações de
envio, renomeação e exclusão são auditadas. Imagens raster válidas podem gerar
miniaturas privadas em fila, enquanto os demais formatos permanecem disponíveis
para download.

O seletor contextual insere no Markdown uma Menção a arquivo como link comum
para o UUID, preservando o rótulo histórico. A Menção a arquivo não concede
acesso: a autorização continua sendo verificada no destino. Reuniões podem
receber compartilhamentos explícitos e revogáveis de Arquivos de Projetos e
Tarefas relacionadas, sem transferir a propriedade.

Portanto, a implementação não incorporou fotos, vídeos ou documentos dentro do
texto. Ela entregou Arquivos privados, miniaturas em cards, links seguros e
compartilhamento controlado; a incorporação direta de mídias continua fora do
escopo concluído.

As decisões de segurança e propriedade estão no
[ADR de Arquivos](adr/0003-modelo-seguranca-e-compartilhamento-de-arquivos.md).

---

## Menções estruturadas

**Implementado em:** 07/2026

O editor oferece autocomplete contextual para Menções a usuários e grava a
sintaxe estável `@[Nome](mention:user:ID)` somente após uma seleção explícita.
O Markdown continua sendo a fonte editorial da verdade; a tabela `mentions` é
um índice derivado, atualizado transacionalmente, limpo quando a fonte deixa de
estar disponível e reconstruível pelo comando `mentions:rebuild`.

A estrutura do índice aceita fontes polimórficas em Projetos, Tarefas, Reuniões,
itens de pauta e comentários. A implementação de destinos, porém, está
restrita a usuários: não há Menções implementadas para Projetos ou Tarefas.
Somente participantes elegíveis do contexto aparecem no autocomplete, e a
renderização usa o nome atual e as permissões de visualização sem reescrever o
Markdown original.

Notificações causadas por Menções, caixa de entrada, backlinks e consultas
dedicadas de Menções não fazem parte desta entrega.

Consulte o [ADR de Menções](adr/0005-generalizar-mencoes-para-entidades.md).

---

## Acompanhamento de entidades e resumos por e-mail

**Implementado em:** 07/2026

Usuários podem ativar ou desativar o acompanhamento de Projetos, Tarefas e
Reuniões que podem visualizar. Eventos relevantes são acumulados por alguns
minutos e enviados em um resumo por e-mail, com autor, data, contexto, detalhes
e link para o recurso quando disponível. O próprio usuário que executou a ação
não recebe o evento correspondente.

Essa implementação substituiu os envios pontuais de vários eventos por um
modelo de acompanhamento opt-in e digest agrupado. Não há inbox interna nem
notificações automáticas para qualquer participante que não tenha ativado o
acompanhamento.

---

## Buscas contextuais e filtros de tarefas

**Implementado em:** 06/2026

Foram adicionadas buscas reativas nos contextos em que o volume de dados exige
localização rápida. Projetos podem ser encontrados por nome, descrição e tags;
subprojetos, por nome; e tarefas, por título, projeto, prioridade e responsáveis.
As buscas funcionam nas visualizações pessoais e de projeto, inclusive em lista
e Kanban, exibem o estado sem resultados e atualizam as contagens visíveis.

As tarefas também podem ser filtradas entre todas ou somente as atribuídas ao
usuário e entre concluídas ou pendentes, com preferências mantidas na sessão. A
implementação é contextual e majoritariamente executada no front-end sobre os
dados já autorizados e carregados; ainda não existe uma barra única de busca
para todas as entidades.
