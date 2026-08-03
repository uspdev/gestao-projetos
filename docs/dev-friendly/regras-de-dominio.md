# Regras de domínio e pontos de aplicação

Este guia concentra invariáveis e efeitos de negócio que atravessam Models,
Policies, Form Requests, controllers e jobs. Ele complementa o
[CONTEXT.md](../../CONTEXT.md) e os ADRs; não substitui a implementação nem
autoriza mudanças de comportamento sem atualização proporcional de testes e
documentação.

## Onde uma regra deve ser aplicada

| Tipo de regra | Ponto principal | Exemplo |
| --- | --- | --- |
| Autorização por recurso | Policy e `FormRequest::authorize()` | editar Tarefa, visualizar Arquivo |
| Forma e consistência de dados | Model, Form Request e transação | representação exclusiva de Item de pauta |
| Disponibilidade de funcionalidade | resolução de módulos e Policy | Tarefas só existem quando `tasks` está ativo |
| Efeito assíncrono | job despachado após confirmação da transação | miniatura e resumo de acompanhamento |
| Recuperação de índice derivado | comando Artisan | `mentions:rebuild` |

Não aplique uma regra somente na interface. Os controladores e endpoints devem
continuar protegidos quando uma requisição for montada fora da tela.

## Projetos, subprojetos e módulos

Projetos podem ser independentes, organizacionais ou subprojetos. Um vínculo de
subprojeto exige, simultaneamente:

- pai raiz do tipo organizacional;
- filho raiz, sem filhos próprios;
- Projetos distintos;
- ao menos um Admin direto em comum;
- filho não organizacional quando a criação ocorre diretamente sob o pai.

O vínculo não funde membros, módulos, Tarefas, Reuniões, Arquivos ou
configurações. A herança de permissões regula apenas visualização até que a
pessoa faça ingresso voluntário no subprojeto; permissões de contribuição
sempre dependem de vínculo local. A role local tem precedência sobre a
herdada. Consulte também [Permissões](../user-friendly/permissoes.md).

O tipo de Projeto define quais módulos são permitidos e pode marcá-los como
obrigatórios ou não editáveis. A configuração específica do Projeto é uma
sobrescrita do padrão do tipo, exceto que uma sobrescrita não pode desligar um
módulo obrigatório. Antes de criar, listar ou alterar um recurso de módulo,
use `Module::isEnabledForProject()` e a Policy adequada; não infira a
disponibilidade apenas da existência de dados relacionados.

## Ciclos e bloqueios

Tarefas com status `DONE` são bloqueadas para edição, exclusão, atribuições e
operações de Arquivos. A alteração de status continua permitida para reabrir a
Tarefa. A primeira atribuição muda uma Tarefa `NEW` para `ASSIGNED`; remover os
responsáveis não altera automaticamente o status.

Reuniões concluídas bloqueiam a Pauta e as Anotações prévias gerais e de itens.
Ata e Transcrição permanecem editáveis. Itens de pauta devem ter exatamente
uma representação: Projeto, Tarefa ou título independente.

Projetos, Tarefas e Reuniões usam exclusão lógica. A exclusão de um Projeto
propaga a exclusão lógica para suas Tarefas e a restauração recupera somente as
Tarefas marcadas como removidas por esse Projeto. Arquivos individuais são uma
exceção: sua exclusão é definitiva.

## Markdown e Menções

Os campos Markdown são `Project.description`, `Task.description`,
`Meeting.notes`, `MeetingItem.notes` e `Comment.text`. Ata e Transcrição são
texto simples. O `MarkdownRenderer` é a única referência para renderização e
pré-visualização segura; não renderize Markdown diretamente em Blade.

O Markdown bruto é a fonte editorial da verdade. A tabela `mentions` é índice
derivado, não deve receber edição manual e é sincronizada na mesma transação do
salvamento do campo. A sintaxe canônica é
`@[Rótulo histórico](mention:tipo:chave)`. Destinos aceitos são `user`,
`project`, `task`, `meeting` e `file`; fontes aceitas são `project`, `task`,
`meeting`, `meeting_item` e `comment`.

Ao criar ou alterar um campo Markdown, a sequência é:

1. validar novas Menções com `MentionManager` no contexto da fonte;
2. persistir o Markdown;
3. sincronizar o índice no mesmo bloco transacional.

Menções históricas que perderam acesso não podem impedir uma edição sem relação
com elas. Na leitura, porém, a autorização é reavaliada para cada pessoa. Uma
Menção nunca concede acesso ao destino.

## Arquivos e compartilhamento

`Media` só aceita Projeto, Tarefa ou Reunião como proprietário. UUID, conteúdo,
proprietário, autor do envio, nome original, armazenamento, MIME e tamanho são
imutáveis; somente o Nome exibido pode mudar. A Policy de Arquivos precisa ser
usada também para download, metadados, miniatura e navegação, que devem ocultar
um recurso inacessível como inexistente.

`meeting_file_shares` é a única exceção explícita de acesso entre contextos. A
validação de origem pertence a `MeetingFileShareController`: Arquivo de Projeto
vinculado ou incluído na Pauta, ou Arquivo de Tarefa incluída na Pauta. Uma
Menção a arquivo não cria compartilhamento. Não transforme a hierarquia de
Projetos em atalho para acesso a Arquivos.

## Acompanhamentos e auditoria

O acompanhamento é opt-in para Projeto, Tarefa e Reunião. Eventos criam
pendências por destinatário após a transação; toda nova atividade do
destinatário adia o resumo conforme `projetos.watching.digest_minutes`. Antes
de enviar, `SendWatchDigest` confirma novamente acompanhamento e visualização.
O autor da ação não recebe o próprio evento.

A auditoria registra alterações de modelos e relações relevantes, além de
operações de Arquivos. A Transcrição não é copiada para `activity_log`: apenas
tamanho e hash são guardados. A manutenção e a recuperação operacional estão
em [Índice de Menções e auditoria](operacao/indice-de-mencoes-e-auditoria.md).

## Checklist para alterar comportamento

1. Verifique o ADR relacionado e o vocabulário em `CONTEXT.md`.
2. Mantenha a regra em Policy/Form Request/Model conforme sua natureza; não só
   em Blade ou JavaScript.
3. Preserve transações que combinam conteúdo e índices ou efeitos posteriores.
4. Cubra a regra e ao menos uma negação de autorização ou estado bloqueado em
   testes Feature/Unit proporcionais ao risco.
5. Atualize este guia, o guia de usuário afetado e um ADR quando a decisão for
   difícil de reverter.
