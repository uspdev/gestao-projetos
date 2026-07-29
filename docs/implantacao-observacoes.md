# Plano de implementação de acompanhamentos (watches)

## Objetivo

Permitir que uma pessoa acompanhe um Projeto, uma Tarefa ou uma Reunião e
receba por e-mail um resumo das atividades relevantes ocorridas nesses
recursos. O acompanhamento é uma preferência individual: não altera membros,
permissões nem a visibilidade do recurso e não é herdado entre Projeto,
subprojeto, Tarefa e Reunião.

Este documento descreve a estrutura já iniciada no código e o plano para
consolidá-la em produção. O termo de domínio usado na interface é
**acompanhamento**; `Watch`, `watches` e `watchable` permanecem nomes técnicos
do código e do banco.

## Escopo

Inclui:

- ativar e desativar o acompanhamento por usuário e recurso;
- preservar acompanhamentos de Tarefas originados em `task_user`;
- registrar atividades pendentes por destinatário;
- agrupar atividades em um resumo assíncrono por e-mail;
- revalidar acesso e preferência antes do envio;
- oferecer pontos de extensão para novos recursos e eventos.

Ficam fora do escopo inicial notificações instantâneas, caixa de entrada no
sistema, preferências por tipo de evento, herança automática e acompanhamento
de itens de pauta.

## Modelo de dados

### Tabela `watches`

Representa a preferência de acompanhamento. A relação polimórfica permite que
o mesmo modelo seja usado por Projetos, Tarefas e Reuniões.

| Coluna | Tipo | Regra e finalidade |
| --- | --- | --- |
| `id` | bigint | Chave primária. |
| `user_id` | FK para `users.id` | Pessoa que acompanha; obrigatório. |
| `watchable_type` | string | Alias morfológico do recurso, como `project`, `task` ou `meeting`. |
| `watchable_id` | bigint sem sinal | Identificador do recurso acompanhado. |
| `created_at`, `updated_at` | timestamp | Auditoria temporal da preferência. |

Índices e invariáveis:

- `watches_user_target_unique` em
  (`user_id`, `watchable_type`, `watchable_id`) impede duplicidade;
- `watches_target_index` em (`watchable_type`, `watchable_id`) atende a busca
  dos destinatários de uma atividade;
- a associação polimórfica não recebe chave estrangeira no banco; o tipo é
  validado na aplicação e o recurso é resolvido antes de ativar ou desativar.

### Tabela `pending_watch_notifications`

É uma caixa de saída transitória para o resumo. Cada linha é uma atividade que
aguarda envio a uma pessoa específica; ela deve ser apagada depois do
processamento, com ou sem envio, para não reaparecer em resumos futuros.

| Coluna | Tipo | Regra e finalidade |
| --- | --- | --- |
| `id` | bigint | Chave primária e marcador do trabalho mais recente. |
| `user_id` | FK para `users.id` | Destinatário do resumo; obrigatório. |
| `watchable_type`, `watchable_id` | string + bigint | Recurso em que a atividade ocorreu. |
| `event_type` | string(80) | Tipo estável do evento, definido por `WatchEventType`. |
| `actor_id` | FK anulável para `users.id` | Pessoa que causou a atividade; `NULL` quando removida. |
| `title` | string | Rótulo do recurso, preservado para o e-mail. |
| `summary` | text | Descrição curta da atividade. |
| `details` | text anulável | Conteúdo complementar, como o texto de um Comentário. |
| `url` | text anulável | Destino do link no e-mail; pode ser ausente em recurso removido. |
| `occurred_at` | timestamp | Momento em que a atividade foi registrada. |
| `send_after` | timestamp | Limite de espera do resumo. |
| `created_at`, `updated_at` | timestamp | Auditoria temporal. |

Índices previstos na migração:

- `pending_watch_user_send_index` em (`user_id`, `send_after`) para localizar
  resumos vencidos de um destinatário;
- `pending_watch_user_target_index` em
  (`user_id`, `watchable_type`, `watchable_id`) para remover pendências de um
  acompanhamento cancelado.

## Contrato dos recursos acompanháveis

Todo recurso elegível implementa `App\Contracts\Watchable`:

```php
public function watchLabel(): string;
public function watchUrl(): ?string;
public function watchCanBeViewedBy(User $user): bool;
```

| Recurso | Rótulo | URL | Regra de visualização |
| --- | --- | --- | --- |
| `Project` | `name` | `projects.show` | A pessoa é visualizadora do Projeto. |
| `Task` | `title` | `tasks.show` | O módulo de Tarefas está ativo e a pessoa vê o Projeto da Tarefa. |
| `Meeting` | `title` | `projects.meetings.show` para um Projeto vinculado | Existe Projeto vinculado, o módulo de Reuniões está ativo e a pessoa vê esse Projeto. |

Ao introduzir outro tipo, ele deve implementar o contrato, receber um alias
morfológico e ser incluído em um mapa específico de recursos acompanháveis.
Embora a implementação atual resolva a rota pelo `CommentableMap`, o plano é
criar `WatchableMap` para não acoplar os tipos acompanháveis aos tipos que
aceitam Comentários.

## Métodos e responsabilidades

| Componente | Método ou ação | Responsabilidade |
| --- | --- | --- |
| `WatchController` | `update()` | Resolve o recurso, autoriza leitura e ativa o acompanhamento do usuário autenticado. |
| `WatchController` | `destroy()` | Resolve e autoriza o recurso, desativa a preferência e limpa suas pendências. |
| `Watch` | `enableFor(int $userId, Watchable $watchable)` | Executa `upsert` idempotente em `watches`. |
| `Watch` | `disableFor(int $userId, Watchable $watchable)` | Em transação, remove a preferência e as pendências daquele recurso; reagenda o resumo restante do destinatário. |
| `PendingWatchNotification` | `addForWatchers(...)` | Localiza os acompanhantes do recurso, exceto quem realizou a ação, e cria uma pendência para cada um. |
| `PendingWatchNotification` | `addForUser(...)` | Bloqueia o usuário, confirma que a preferência ainda existe, adia o resumo anterior e cria a nova pendência. |
| `SendWatchDigest` | `handle()` | Processa somente o trabalho mais recente do destinatário, revalida acesso e preferência, envia o resumo válido e remove pendências processadas. |
| `WatchDigest` | `build()` | Monta o assunto e a view `emails.watch.digest`. |
| `TaskUser` | eventos `created` e `deleted` | Mantém a compatibilidade: vincular alguém a uma Tarefa ativa o acompanhamento; desvincular desativa-o. |

Os métodos que alteram pendências devem usar transação e `lockForUpdate()` na
linha do destinatário. Isso serializa eventos concorrentes do mesmo usuário e
evita que um trabalho atrasado envie um resumo antes de uma atividade mais
recente ser agrupada.

## Eventos iniciais

Os valores devem permanecer no enum `App\Enums\Watch\WatchEventType`, para que
sejam estáveis para filtros futuros e auditoria.

| Evento | Origem | Resumo esperado |
| --- | --- | --- |
| `comment.created` | criação de Comentário em Projeto, Tarefa ou Reunião | `Novo comentário.` com o texto em `details`. |
| `task.completed` | transição de status da Tarefa para concluída | `Tarefa concluída.` |
| `meeting.updated` | agendamento ou atualização de Reunião | `Reunião agendada.` ou `Reunião atualizada.` |
| `meeting.removed` | remoção de Reunião | `Reunião removida.`, sem URL. |
| `subproject.linked` | vínculo de subprojeto | informa o Projeto pai. |
| `subproject.unlinked` | desvínculo de subprojeto | informa o Projeto organizacional anterior. |

Em toda nova ação de domínio, a chamada a `addForWatchers()` deve ocorrer após
a persistência bem-sucedida e com o ator autenticado. O ator nunca recebe a
notificação da própria ação.

## Fluxo de processamento

```text
Pessoa ativa acompanhamento
  -> WatchController autoriza visualização
  -> Watch::enableFor grava uma preferência única

Atividade em recurso acompanhado
  -> PendingWatchNotification::addForWatchers
  -> uma pendência por destinatário
  -> todas as pendências do destinatário recebem novo send_after
  -> SendWatchDigest é enfileirado após o commit

Trabalho executado após send_after
  -> confirma que é a pendência mais recente do destinatário
  -> revalida acesso ao recurso e existência do acompanhamento
  -> envia WatchDigest apenas com itens válidos
  -> remove todas as pendências lidas do destinatário
```

O intervalo é configurado em `projetos.watching.digest_minutes` e inicia em
cinco minutos. A semântica atual é um resumo por destinatário, não um resumo
por recurso: qualquer nova atividade reinicia a espera das pendências desse
destinatário.

## Execução da fila de e-mails

`SendWatchDigest` implementa `ShouldQueue`. Após gravar uma pendência,
`PendingWatchNotification` executa, depois da confirmação da transação:

```php
SendWatchDigest::dispatch($pending->id)->delay($sendAfter);
```

Com `QUEUE_CONNECTION=database`, o Laravel grava esse trabalho na tabela
`jobs`, com o horário de disponibilidade igual a `send_after`. O worker lê a
tabela continuamente, executa o job quando o prazo vence e o job chama
`Mail::to(...)->send(new WatchDigest(...))`. Não há comando agendado para esse
fluxo: `php artisan schedule:run` não é necessário para enviar os resumos.

Em desenvolvimento, iniciar o worker em outro terminal:

```sh
php artisan queue:work database --queue=default --sleep=3 --tries=4 --timeout=60
```

Em produção, o mesmo comando deve permanecer sob um supervisor de processos
(por exemplo, Supervisor ou systemd), com reinício automático. Após publicar
uma versão que altere código de jobs, executar:

```sh
php artisan queue:restart
```

O worker encerra de forma segura depois do trabalho atual e o supervisor o
inicia novamente usando o código novo. A variável `QUEUE_CONNECTION` deve ser
`database` (o valor de fallback da configuração é `sync`); `MAIL_MAILER` e as
demais credenciais de e-mail também precisam estar configurados no ambiente.

O job tenta até quatro vezes. Em falhas, espera 60, 300 e 900 segundos entre
as novas tentativas. Acompanhar as tabelas `jobs` e `failed_jobs`, além dos
logs da aplicação, para identificar trabalhos bloqueados ou falhas de envio.
