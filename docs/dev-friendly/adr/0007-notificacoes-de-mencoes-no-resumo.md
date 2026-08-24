# Notificações de Menções no resumo

**Status:** aceito

## Contexto

Menções a Usuário já eram indexadas e exibidas, mas não chamavam a atenção do
destinatário. O resumo de acompanhamento já possui atraso e revalidação de
acesso adequados para esse efeito.

## Decisão

Uma Menção nova a Usuário cria uma pendência `mention.created` para o
destinatário, agrupada no `WatchDigest` conforme
`projetos.watching.digest_minutes`. O autor da Menção não recebe a própria
notificação.

O acompanhamento geral de Menções reutiliza `watches`, com o tipo técnico
`mention` e o próprio ID do usuário como chave. A Menção ativa esse vínculo; a
dashboard oferece o mesmo controle de ativar e desativar dos acompanhamentos
de recursos, em um card genérico de preferências gerais. A desativação registra
o opt-out no mesmo espaço para que uma Menção futura não reative a preferência
automaticamente; o botão de ativar remove esse opt-out.

O envio confirma novamente o acompanhamento geral, a existência da relação
indexada e a visualização autorizada da origem. Reconstruções do índice não
disparam notificações.

Não é criada uma tabela adicional de preferências. Novos acompanhamentos
gerais poderão reutilizar o card e o mesmo espaço de `watches` com tipos
próprios.

Esta decisão amplia o escopo de notificações declarado no ADR 0005 somente para
Menções a Usuário; as demais Menções continuam sem efeitos de notificação.
