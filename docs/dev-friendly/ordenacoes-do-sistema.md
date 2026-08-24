# Ordenações do sistema

Este documento registra as ordenações observáveis e as ordenações internas que
alteram uma escolha do sistema. O inventário foi feito sobre o código de
produção, as views, o JavaScript carregado pela aplicação e as migrations. Os
testes e os exemplos de documentação não são fontes de comportamento em tempo
de execução.

## Helpers

- `ASC` significa crescente e `DESC`, decrescente.
- `latest()` sem argumento equivale a `created_at DESC`; `oldest()` sem
  argumento equivale a `created_at ASC`.
- Uma coluna mencionada depois de outra é um critério de desempate. Quando não
  há critério adicional, linhas empatadas não têm ordem garantida.
- Ordenações textuais dependem da collation configurada no banco. Portanto,
  regras de acentos, maiúsculas e minúsculas não são definidas pela aplicação.
- A posição de `NULL` também depende do banco, exceto quando a consulta trata o
  valor explicitamente. Atualmente apenas `Task::orderByPriority()` força os
  nulos para o fim.

## Projetos

| Contexto | Critérios efetivos | Nulos e desempates | Fonte |
|---|---|---|---|
| Lista “Meus projetos” | Atividade mais recente: `projects.updated_at DESC` | Sem desempate explícito. A atualização de entidades relacionadas pode tocar o projeto, conforme o uso de `updated_at` descrito pelo próprio escopo. | [`ProjectController::index`](../../app/Http/Controllers/ProjectController.php#L41), [`Project::scopeOrderByActivity`](../../app/Models/Project.php#L476) |
| Cartões de subprojetos de um projeto organizacional | `projects.updated_at DESC` | Sem desempate explícito. | [`Project::subprojects`](../../app/Models/Project.php#L359), [`Project::scopeOrderByActivity`](../../app/Models/Project.php#L476) |
| Projetos fixados na dashboard | `projects.name ASC` | Sem desempate explícito; collation do banco. | [`User::pinnedProjects`](../../app/Models/User.php#L64) |
| Busca de candidatos a subprojeto | `projects.name ASC` | Limite de 50 depois da ordenação; nomes iguais não têm desempate. | [`ProjectController`](../../app/Http/Controllers/ProjectController.php#L265) |
| Seletores de vinculação de subprojeto e de projeto pai | Projeto: `name ASC`. Administradores carregados: `project_user.created_at ASC`. | Projeto sem desempate. Entre administradores, o primeiro vínculo é usado primeiro; vínculos no mesmo instante não têm desempate. | [`Project::linkableSubprojects`](../../app/Models/Project.php#L793), [`Project::linkableParents`](../../app/Models/Project.php#L832) |
| Lista de membros por subprojeto | Subprojetos por `name ASC`; membros por `name ASC`, pois a relação `Project::users()` possui essa ordem padrão. | Sem desempate além do nome. | [`ProjectController::subprojectMembers`](../../app/Http/Controllers/ProjectController.php#L675), [`Project::users`](../../app/Models/Project.php#L170) |
| Usuários/membros selecionáveis para projeto ou tarefa | `users.name ASC` | Sem desempate além do nome. | [`User::scopeSelectableToProject`](../../app/Models/User.php#L155), [`User::scopeAssignableToProject`](../../app/Models/User.php#L177) |
| Busca de pessoas no Replicado | Remove `codpes` duplicado, depois `nompesttd ASC`, e então limita a 50. | Pessoas com o mesmo nome preservam a ordem recebida do Replicado; `codpes` não desempata. | [`ProjectMemberController::selectable`](../../app/Http/Controllers/ProjectMemberController.php#L162) |
| Tipos de projeto na criação | Tipo por `name ASC`; módulos de cada tipo por `name ASC`. | Sem desempates explícitos. | [`ProjectController::create`](../../app/Http/Controllers/ProjectController.php#L114) |
| Tags nos formulários de projeto | `order_column ASC`, herdado de `Spatie\Tags\Tag::withType()`, e depois `tags.name ASC`; restritas ao tipo `projects`. | `order_column` aceita `NULL` e sua posição depende do banco; nome não desempata homônimos. | [`Tag::forProjects`](../../app/Models/Tag.php#L36), [`Spatie\Tags\Tag::scopeWithType`](../../vendor/spatie/laravel-tags/src/Tag.php#L31), [`SortableTrait::scopeOrdered`](../../vendor/spatie/eloquent-sortable/src/SortableTrait.php#L39) |
| Módulos exibidos nas configurações e no menu do projeto | `name ASC` depois de resolver configuração e herança. A relação direta de módulos do projeto também usa `name ASC`, e o menu preserva essa sequência após retirar Fases. | Sem desempate além do nome. | [`Module::resolveForProject`](../../app/Models/Module.php#L120), [`Project::modules`](../../app/Models/Project.php#L412), [`Project::activeModulesForMenu`](../../app/Models/Project.php#L277) |

## Tarefas

A prioridade canônica é numérica: Urgente = 1, Alta = 2, Média = 3 e Baixa =
4 ([`TaskPriority`](../../app/Enums/Task/TaskPriority.php#L5)).

| Contexto | Critérios efetivos | Nulos e desempates | Fonte |
|---|---|---|---|
| Lista de tarefas de um projeto | Prioridade definida antes de prioridade ausente; depois `priority ASC`; depois `created_at DESC`. Resultado: Urgente, Alta, Média, Baixa, sem prioridade; dentro da mesma prioridade, tarefa mais nova primeiro. | O `NULL` é explicitamente enviado ao fim. Mesmo `priority` e `created_at` não têm desempate por `id`. | [`TaskController::indexProject`](../../app/Http/Controllers/TaskController.php#L59), [`Task::scopeOrderByPriority`](../../app/Models/Task.php#L168) |
| Lista de tarefas na dashboard | Mesma regra da lista do projeto: prioridade canônica, sem prioridade no fim, e `created_at DESC`. | Mesmo comportamento de nulos e empates. | [`User::tasksByStatus`](../../app/Models/User.php#L98), [`Task::scopeOrderByPriority`](../../app/Models/Task.php#L168) |
| Kanban de um projeto | Prioridade definida antes de prioridade ausente; depois `priority ASC`, `completed_at ASC` e `created_at DESC`; em seguida os resultados são agrupados por status. | O `NULL` de prioridade é explicitamente enviado ao fim. Conclusão ausente depende do banco. Sem desempate por `id`. | [`TaskController::indexProject`](../../app/Http/Controllers/TaskController.php#L69), [`Task::scopeOrderByPriority`](../../app/Models/Task.php#L168) |
| Kanban da dashboard | Prioridade definida antes de prioridade ausente; depois `priority ASC`, Projeto por `name ASC` e `created_at DESC`; em seguida agrupa por status. | O `NULL` de prioridade é explicitamente enviado ao fim. Projetos ou datas iguais não têm desempate por `id`. | [`User::tasksByStatus`](../../app/Models/User.php#L98), [`Task::scopeOrderByPriority`](../../app/Models/Task.php#L168) |
| Responsáveis mostrados no cartão Kanban | `users.name ASC`, em memória. | Sem desempate além do nome. | [`kanban-task-card.blade.php`](../../resources/views/module-tasks/partials/kanban/kanban-task-card.blade.php#L80) |
| Tags nos formulários de tarefa | `order_column ASC`, herdado de `Spatie\Tags\Tag::withType()`, e depois `tags.name ASC`; restritas ao tipo `tasks`. | `order_column` aceita `NULL` e sua posição depende do banco; nome não desempata homônimos. | [`Tag::forTasks`](../../app/Models/Tag.php#L46), [`Spatie\Tags\Tag::scopeWithType`](../../vendor/spatie/laravel-tags/src/Tag.php#L31) |

As tags já vinculadas a projetos e tarefas também são carregadas por
`order_column ASC`, devido à relação fornecida por `Spatie\Tags\HasTags`. Essa é
a ordem usada nos badges dos cartões e tabelas; não há desempate declarado
([relação do pacote](../../vendor/spatie/laravel-tags/src/HasTags.php#L76),
[coluna anulável](../../database/migrations/2026_04_22_172311_create_tag_tables.php#L11)).

### Ordenação interativa da tabela de tarefas

A tabela usa o componente `datatable-simples`. Sua configuração inicial possui
`order: []`, portanto não substitui a ordem recebida do backend, mas mantém a
ordenação por coluna habilitada para ação do usuário
([configuração instalada do tema](../../vendor/uspdev/laravel-usp-theme/resources/views/blocos/datatable-simples.blade.php#L206)).

Ao clicar nas colunas de data, o DataTables compara o valor ISO
`Y-m-d H:i:s` fornecido em `data-order`, em vez da data localizada exibida. Um
valor ausente vira string vazia. Prioridade e status não possuem `data-order`:
quando o usuário ordenar essas colunas, será usado o texto apresentado, e não a
ordem canônica do enum. Assim, ordenar manualmente “Prio.” é uma ordenação
alfabética dos rótulos, diferente da ordenação inicial por importância
([tabela e metadados de data](../../resources/views/module-tasks/partials/show/card-tasks-table.blade.php#L11),
[rótulo de prioridade](../../resources/views/module-tasks/partials/priority-badge.blade.php#L1)).

## Reuniões, pauta e comentários

| Contexto | Critérios efetivos | Nulos e desempates | Fonte |
|---|---|---|---|
| Lista de reuniões do projeto e reuniões da dashboard | `status DESC`, depois `scheduled_at ASC`. | O status é o código textual, não uma ordem de negócio declarada. Com os códigos atuais, a ordem lexical descendente é `SCHEDULED`, `ONGOING`, `DRAFT` e `COMPLETED`; concluídas são normalmente filtradas. `scheduled_at` aceita `NULL` e sua posição depende do banco. Sem terceiro desempate. | [`MeetingController::index`](../../app/Http/Controllers/MeetingController.php#L39), [`User::scheduledMeetings`](../../app/Models/User.php#L72), [`MeetingStatus`](../../app/Enums/Meeting/MeetingStatus.php#L5) |
| Itens da pauta na tela e na exportação | `meeting_items.order ASC`. | A coluna é não nula, mas não é única por reunião; empates não têm desempate. | [`MeetingController::show`](../../app/Http/Controllers/MeetingController.php#L104), [`MeetingController::export`](../../app/Http/Controllers/MeetingController.php#L167), [migration](../../database/migrations/2026_05_15_090000_create_meeting_items_table.php#L11) |
| Itens copiados ao duplicar reunião | `order ASC` antes da criação das cópias. | Empates preservam a ordem da coleção originalmente carregada, que não é garantida pela relação. | [`Meeting::duplicate`](../../app/Models/Meeting.php#L341) |
| Reuniões oferecidas no modal de duplicação de projeto | `scheduled_at ASC`, depois `title ASC`. | Data ausente depende do banco; títulos iguais não têm desempate. | [`duplicates/modals/project.blade.php`](../../resources/views/duplicates/modals/project.blade.php#L1) |
| Comentários na tela e na exportação de reunião | `created_at ASC` (`oldest()`), do mais antigo para o mais novo. | Sem desempate por `id`. | [`comments/partials/thread.blade.php`](../../resources/views/comments/partials/thread.blade.php#L1), [`MeetingController::export`](../../app/Http/Controllers/MeetingController.php#L171) |
| Projetos na exportação da reunião | `projects.name ASC`. | Sem desempate além do nome. | [`MeetingController::export`](../../app/Http/Controllers/MeetingController.php#L171) |
| Projeto escolhido como contexto de uma reunião | Primeiro projeto visível após `name ASC`. | Sem desempate por `id`; nomes iguais podem alterar o projeto escolhido. | [`Meeting::contextProjectFor`](../../app/Models/Meeting.php#L103), [`UserController`](../../app/Http/Controllers/UserController.php#L124) |

## Arquivos e referências

| Contexto | Critérios efetivos | Nulos e desempates | Fonte |
|---|---|---|---|
| Arquivos próprios nas telas de projeto, tarefa e reunião | `created_at DESC`, depois `id DESC`, com 20 itens por página. | Ordem total para IDs distintos; `created_at` é preenchido pelo modelo. | [`ProjectController::show`](../../app/Http/Controllers/ProjectController.php#L91), [`TaskController::show`](../../app/Http/Controllers/TaskController.php#L152), [`MeetingController::show`](../../app/Http/Controllers/MeetingController.php#L132) |
| Arquivos compartilhados exibidos na reunião | `created_at DESC` por `latest()`. | Sem desempate por `id`. | [`MeetingController::show`](../../app/Http/Controllers/MeetingController.php#L137) |
| Links próprios nas telas de projeto, tarefa e reunião | `created_at DESC`, depois `id DESC`, com 20 itens por página. | Ordem total para IDs distintos; Links compartilhados da Reunião entram depois dos próprios e usam `latest()` sem desempate por `id`. | [`ProjectController::show`](../../app/Http/Controllers/ProjectController.php#L101), [`TaskController::show`](../../app/Http/Controllers/TaskController.php#L156), [`MeetingController::show`](../../app/Http/Controllers/MeetingController.php#L138) |
| Navegador de Arquivos e Links da reunião | Arquivos e Links próprios vêm antes dos compartilhados; duplicatas mantêm a primeira ocorrência. A interface separa cada conjunto nas abas Imagens, Documentos e Links, preservando a sequência dentro de cada aba. | As listas próprias possuem o desempate por `id`; as compartilhadas não. A separação por tipo não reordena os itens dentro de cada aba. | [`components/files/list.blade.php`](../../resources/views/components/files/list.blade.php#L10), [`MeetingController::show`](../../app/Http/Controllers/MeetingController.php#L132) |
| Arquivos oferecidos pelo editor de Markdown | Cada grupo de arquivos próprios usa `created_at DESC`; arquivos compartilhados também usam `created_at DESC`. Na tarefa, arquivos da tarefa vêm antes dos arquivos do projeto; na reunião, próprios antes dos compartilhados. Duplicatas são removidas mantendo a primeira ocorrência. | Sem desempate por `id` dentro dos grupos. | [`FileContextResolver`](../../app/Services/FileContextResolver.php#L20) |
| Destino alternativo de uma menção a arquivo compartilhado | Reuniões por `meetings.id DESC`; dentro da reunião, projetos por `name ASC` e `id ASC`. O primeiro destino visível vence. | O `id` resolve nomes de projeto iguais; reunião de maior ID é tratada como a mais recente, independentemente de datas. | [`FileReferenceNavigator`](../../app/Services/FileReferenceNavigator.php#L146) |

## Acompanhamentos e notificações

| Contexto | Critérios efetivos | Nulos e desempates | Fonte |
|---|---|---|---|
| Recursos acompanhados na dashboard | `watches.created_at DESC`, depois `watches.id DESC`. | O `id` dá desempate total entre registros. | [`UserController::watchedResourcesFor`](../../app/Http/Controllers/UserController.php#L85) |
| Grupos de acompanhamentos na dashboard | Projetos, depois Tarefas, depois Reuniões. Dentro de cada grupo é preservada a ordem decrescente do vínculo de acompanhamento descrita acima. | A separação por tipo não reordena os itens. | [`watches/partials/user-dashboard.blade.php`](../../resources/views/watches/partials/user-dashboard.blade.php#L1) |
| Eventos no e-mail de resumo | `occurred_at ASC`, do evento mais antigo para o mais novo. | Sem desempate por `id`; a coluna não é nula. | [`SendWatchDigest`](../../app/Jobs/SendWatchDigest.php#L59) |
| Escolha do lote de resumo ainda vigente | Maior `pending_watch_notifications.id`. | O maior ID determina se o job atual ainda é o último agendado. É uma decisão de processamento, não de apresentação. | [`SendWatchDigest`](../../app/Jobs/SendWatchDigest.php#L45) |
| Migração de acompanhamentos antigos | `task_user.id ASC`, em lotes de 500. | Ordenação operacional para percorrer todos os registros; não altera a apresentação depois da migration. | [migration de acompanhamentos](../../database/migrations/2026_07_27_090000_create_watching_tables.php#L25) |

## Listas exibidas sem ordenação garantida

Uma consulta sem `ORDER BY` não herda uma ordem estável do banco, mesmo quando
parece retornar IDs em ordem durante o desenvolvimento. Os casos observáveis
encontrados são:

| Contexto | Ordem que a tela recebe hoje | Fonte |
|---|---|---|
| Catálogo administrativo de módulos e tags | `Module::all()` e `Tag::all()`, sem ordem. | [`AdminController::index`](../../app/Http/Controllers/AdminController.php#L32) |
| Projetos de cada usuário na administração | A eager load `with('projects')` usa a relação sem `ORDER BY`; os itens dentro de cada usuário são indeterminados. | [`AdminController::index`](../../app/Http/Controllers/AdminController.php#L45), [`User::projects`](../../app/Models/User.php#L123) |
| Projetos selecionáveis ao criar/editar reunião | `Project::availableForMeetings(...)->get()` não acrescenta ordem; ao editar, o projeto da rota pode ser inserido no início, caso esteja ausente. | [`MeetingController`](../../app/Http/Controllers/MeetingController.php#L57), [`HasMeeting::scopeAvailableForMeetings`](../../app/Traits/HasMeeting.php#L33) |
| Opções de projeto e tarefa para a pauta | Projetos vinculados, filhos e tarefas são carregados sem ordem; as opções são concatenadas na ordem recebida. Tipos de item seguem a ordem do array `project`, `task` na configuração. | [`Meeting::projectsForAgenda`](../../app/Models/Meeting.php#L169), [`Meeting::meetingItemFormData`](../../app/Models/Meeting.php#L194), [`config/projetos.php`](../../config/projetos.php#L25) |
| Grupos de fontes de Arquivos compartilháveis em reunião | Primeiro projetos vinculados; depois projetos presentes na pauta; por fim tarefas presentes na pauta. A sequência dentro desses três grupos vem de relações sem `ORDER BY`; os Arquivos de cada proprietário ficam do mais novo para o mais antigo. | [`FileReferenceSelector::shareableMeetingSources`](../../app/Services/FileReferenceSelector.php#L137), [`FileContextResolver::ownedFilesFor`](../../app/Services/FileContextResolver.php#L45) |
| Contextos carregados para decidir visibilidade de reunião | As relações `meeting.projects` não têm ordem; quando o código apenas verifica se algum é visível, a sequência não muda o resultado. Onde o primeiro projeto vira contexto observável, o sistema aplica `name ASC` conforme registrado nas seções anteriores. | [`HasMeeting::meetings`](../../app/Traits/HasMeeting.php#L16), [`Meeting::projects`](../../app/Models/Meeting.php#L94) |
