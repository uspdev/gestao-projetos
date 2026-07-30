# Notificações por e-mail

Esta página descreve os e-mails enviados pela aplicação, seus destinatários e
como cada envio é processado.

## Quando cada e-mail é enviado

| Evento | Destinatário | Tipo de envio | Quando é enviado |
| --- | --- | --- | --- |
| Inclusão de membro em um Projeto | Pessoa incluída no Projeto | Imediato, pela fila | Após a inclusão ser concluída. Não é enviado se a própria pessoa realizou a inclusão. |
| Atribuição de responsável a uma Tarefa | Pessoa atribuída à Tarefa | Imediato, pela fila | Após a atribuição ser concluída. Não é enviado se a pessoa já estava atribuída ou se realizou a própria atribuição. |
| Criação de Comentário em Projeto, Tarefa ou Reunião acompanhada | Pessoas que acompanham o recurso | Resumo agrupado, pela fila | Entra no resumo como `Novo comentário.`, com o texto do Comentário. |
| Conclusão de Tarefa acompanhada | Pessoas que acompanham a Tarefa | Resumo agrupado, pela fila | Entra no resumo quando o status muda para concluída. |
| Agendamento ou atualização de Reunião acompanhada | Pessoas que acompanham a Reunião | Resumo agrupado, pela fila | Entra no resumo como `Reunião agendada.` ou `Reunião atualizada.`. Reuniões em rascunho não geram essa notificação. |
| Remoção de Reunião acompanhada | Pessoas que acompanham a Reunião | Resumo agrupado, pela fila | Entra no resumo como `Reunião removida.`. Não contém link para a Reunião removida. Reuniões em rascunho não geram essa notificação. |
| Vínculo de Subprojeto acompanhado | Pessoas que acompanham o Subprojeto | Resumo agrupado, pela fila | Entra no resumo informando o Projeto pai. |
| Desvínculo de Subprojeto acompanhado | Pessoas que acompanham o Subprojeto | Resumo agrupado, pela fila | Entra no resumo informando o Projeto organizacional anterior. |

Em todos os eventos de acompanhamento, a pessoa que executou a ação não recebe
notificação sobre a própria ação.

## Envio imediato pela fila

Os e-mails de inclusão de membro e de atribuição de Tarefa são colocados na fila
com `Mail::queue()`. O worker da fila executa o envio assim que processar o
trabalho; portanto, eles não aguardam a formação de um resumo.

## Resumo de acompanhamentos

O resumo é destinado apenas a pessoas que ativaram o acompanhamento do Projeto,
da Tarefa ou da Reunião relacionada. Ele é um resumo por destinatário, e não
por recurso: atividades em diferentes recursos acompanhados podem compor o
mesmo e-mail.

Cada nova atividade adia o envio de todas as pendências daquele destinatário.
O intervalo padrão é de cinco minutos, configurado em
`projetos.watching.digest_minutes`. Quando esse prazo vence, o trabalho
`SendWatchDigest` é executado pela fila e envia um único e-mail com as
atividades ainda válidas.

Antes do envio, a aplicação confirma que a pessoa ainda acompanha cada recurso
e ainda pode visualizá-lo. Itens que não atendem a essas condições são
descartados e não aparecem no resumo.
