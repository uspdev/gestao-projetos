# Notificações por entidade

## Visão técnica

Projeto, tarefa e reunião possuem uma preferência binária por usuário. Uma linha
em `watches` significa **Receber notificações**; a ausência significa **Não
receber notificações**. As entidades são independentes, sem herança entre
projeto, tarefas, reuniões ou subprojetos.

A implementação contém:

- `WatchController`, com rotas idempotentes `PUT` e `DELETE`;
- `Watch`, responsável por ativar ou remover a preferência;
- `PendingWatchNotification`, que registra eventos para cada destinatário;
- `SendWatchDigest`, job atrasado que valida e envia o resumo;
- `WatchDigest`, e-mail único com as atividades válidas.

## Persistência

A migração `2026_07_27_090000_create_watching_tables` cria:

- `watches`: usuário e destino polimórfico, únicos por usuário/entidade;
- `pending_watch_notifications`: destinatário, entidade, evento, ator, textos,
  URL, horário do evento e `send_after`.

Na própria migração, responsáveis já atribuídos recebem uma preferência para
suas tarefas. Novas atribuições criam a preferência; desatribuições removem a
preferência e as pendências da tarefa, mesmo quando ela foi ativada manualmente.

## Resumo com janela deslizante

Ao registrar uma atividade, o sistema:

1. seleciona somente observadores diretos da entidade e exclui o ator;
2. bloqueia cada destinatário;
3. atualiza todas as pendências dele para `agora + 5 minutos`;
4. cria a nova pendência e agenda `SendWatchDigest` com seu ID.

O intervalo vem de `projetos.watching.digest_minutes`.

O job bloqueia o destinatário e só continua se seu ID ainda for o mais recente
e `send_after` estiver vencido. Antes do envio, revalida a preferência e o
acesso a cada entidade. Jobs antigos encerram sem efeito; eventos inválidos são
descartados; falhas de e-mail mantêm as pendências para retentativa; o sucesso
remove as pendências processadas.

## Eventos

Entram no resumo:

- novo comentário em projeto, tarefa ou reunião;
- conclusão de tarefa;
- atualização, status e remoção de reunião fora de rascunho;
- vínculo e desvínculo de subprojeto, somente para observadores do subprojeto.

Comentários são exibidos como texto escapado. `ProjectUserAdded` e
`TaskAssigned` continuam como e-mails pessoais diretos e não alteram a janela
do resumo.

## Implantação

Configure a fila em banco, execute a migração e mantenha um worker:

```dotenv
QUEUE_CONNECTION=database
```

```bash
php artisan migrate
php artisan queue:work
```

