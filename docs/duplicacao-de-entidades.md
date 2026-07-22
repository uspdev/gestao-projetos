# Duplicação de entidades

Este documento descreve o comportamento implementado para duplicar tarefas,
reuniões e projetos. A cópia sempre cria uma nova entidade: não reaproveita
auditoria, timestamps, comentários ou histórico de atividades da origem.

## 1. Cópia de tarefa

A tarefa só pode ser duplicada dentro do mesmo projeto. O formulário sugere o
título original com o sufixo `(Cópia)` e permite ajustar o título, a data de
início e a data de entrega antes da criação.

Copiar:

- título, com possibilidade de alteração antes da criação;
- descrição;
- prioridade;
- tags;
- responsáveis;
- datas de início e de entrega, como valores inicialmente preenchidos.

Reiniciar:

- status: `Nova` quando não houver responsáveis e `Atribuída` quando houver;
- `completed_at`, que fica vazio.

Não copiar:

- comentários;
- histórico de atividades;
- participação em pautas de reuniões;
- Arquivos.

Os comentários e os itens de pauta pertencem ao contexto da tarefa original,
não à definição reutilizável dela.

## 2. Cópia de reunião

A cópia direta de uma reunião preserva os projetos aos quais a reunião original
está vinculada. O formulário sugere o título original com o sufixo `(Cópia)` e
exige que a nova data e hora sejam confirmadas. Quando a data original já
passou, a interface avisa que a reunião deve ser remarcada.

Copiar:

- título, com possibilidade de alteração antes da criação;
- data e hora, inicialmente preenchidas com os valores da reunião original;
- local;
- projetos vinculados;
- Anotações prévias, Ata e Transcrição da reunião;
- itens de pauta e sua ordem.

Nos itens de pauta, são preservados o vínculo com o projeto ou tarefa e o
título de um Item independente. As Anotações prévias de cada item não são
copiadas, pois registram o que foi discutido na reunião anterior.

Reiniciar:

- status para `Agendada`;
- Anotações prévias dos itens de pauta.

Não copiar:

- comentários;
- histórico de atividades;
- Arquivos.

## 3. Cópia de projeto

Projetos organizacionais e projetos que possuem subprojetos não podem ser
duplicados. A cópia é sempre um projeto independente, sem projeto pai. O nome
sugerido é o original com o sufixo `(Cópia)` e pode ser alterado antes da
criação.

Copiar automaticamente:

- descrição;
- tipo de projeto;
- tags;
- visibilidade;
- herança de permissões;
- configuração e personalizações dos módulos, inclusive quais módulos estão
  ativos ou desativados.

Reiniciar ou gerar novamente:

- slug, gerado para a nova cópia;
- status para `Rascunho`;
- fase para a fase inicial ativa do tipo de projeto; se não houver módulo de
  fases ou uma fase inicial disponível, a cópia fica sem fase;
- `parent_id`, que fica vazio;
- auditoria e timestamps.

Tarefas, reuniões e membros são dados opcionais. As opções de tarefas e
reuniões só são exibidas e aceitas quando o respectivo módulo está ativo no
projeto original.

### Membros

Ao selecionar **Copiar membros**:

- copiar usuários e seus papéis;
- não copiar a fixação do projeto;
- garantir que a pessoa que criou a cópia seja `ADMIN`; se ela já era membro,
  seu papel é mantido ou elevado para `ADMIN`.

Sem essa opção, somente a pessoa que cria a cópia é adicionada como `ADMIN`.

### Tarefas

Ao selecionar **Copiar tarefas**, são criadas cópias das tarefas do projeto
original com suas descrições, prioridades, tags e datas.

- Com **Copiar membros** selecionado, os responsáveis também são copiados e o
  status da tarefa é recalculado (`Atribuída` quando houver responsáveis ou
  `Nova` quando não houver). Responsáveis que ainda não forem membros da cópia
  passam a integrá-la como `CONTRIBUTOR`.
- Sem **Copiar membros**, as tarefas copiadas não recebem responsáveis e
  mantêm o status da tarefa original; a data de conclusão permanece vazia.

Comentários, histórico de atividades, participação em pautas e Arquivos das
tarefas não são copiados.

### Reuniões

Ao selecionar **Copiar reuniões**, cada reunião do projeto original é copiada
com a mesma data e hora. A interface avisa para conferir as datas e horários
antes de usar as cópias.

Cada reunião copiada fica vinculada somente ao novo projeto, mesmo que a
reunião de origem estivesse vinculada a outros projetos. O restante do
conteúdo segue as regras de cópia de reunião: a cópia nasce como `Agendada`,
preserva Anotações prévias, Ata, Transcrição, local e itens de pauta, mas limpa
as Anotações prévias dos itens.

Não copiar da cópia de projeto:

- comentários;
- histórico de atividades;
- Arquivos;
- subprojetos;
- os vínculos da reunião original com outros projetos.
