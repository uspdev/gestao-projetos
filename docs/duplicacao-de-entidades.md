1. Cópia de tarefa

Duplicar somente dentro do mesmo projeto. Copiar:


 - Título.
 - Descrição.
 - Prioridade.
 - Tags.
 - Responsáveis.
 - Datas como valores inicialmente preenchidos.
 - Arquivos futuramente.

Reiniciar o status, para o status inicial definido pelo sistema.
completed_at para null.
Auditoria e timestamps.

Não copiar:

Comentários.
Histórico de atividades.
Participação em pautas de reuniões.

Os comentários e itens de reunião pertencem ao contexto da tarefa original, não à definição reutilizável dela.

2. Cópia de reunião

Ao duplicar pela página de um projeto, a nova reunião fica vinculada a todos os projetos que a referencia estava vinculada. Copiar:

 - Título.
 - Local.
 - Itens de pauta, sua ordem. Mas não as notas, pois registram o que foi discutido na reunião anterior, não o que será decido na nova.
 - Ordem dos itens.
 - Projetos vinculados.
 - Arquivos de apoio futuramente.
 - Notas, atas, transcrições, etc 

Reiniciar:
 - Status para SCHEDULED.
 - Auditoria e timestamps.

Data e horário: manter os valores da reunião original. Ao selecionar a cópia de reuniões na duplicação do projeto,
exibir um aviso para que o usuário confira as datas e os horários das reuniões copiadas antes de usá-las.

Não copiar
Comentários.


3. Cópia de projeto

Projeto com subprojetos não pode ser duplicado -> projeto organizacional não pode ser duplicado. 
Tarefas, reuniões e membros são opções -> caso o usário queira copiar as informações de algum desses módulos ele pode escolher.
Configurações, personalizações e tags são copiadas automaticamente.

Dados básicos copiados automaticamente:
 - Descrição.
 - Tipo de projeto.
 - Tags.
 - Visibilidade.
 - Herança de permissões.
 - Configuração e overrides de módulos.
 - Outras personalizações próprias do projeto.

Dados que devem ser reiniciados ou tratados:
Nome: preencher como Cópia de Nome, mas permitir alteração.
Slug: gerar novamente.
Status: iniciar no status inicial do projeto.
Fase: iniciar na fase inicial do tipo de projeto.
parent_id: null.
Auditoria e timestamps.

Se o projeto original estiver em uma fase final, a cópia não deveria nascer nessa mesma etapa..

Nome: [Cópia de Projeto original]

[x] Copiar membros
[ ] Copiar tarefas
[ ] Copiar reuniões

Ao selecionar copiar membros:

Copiar usuários e seus papéis.
Não copiar pinned.
Garantir que o usuário que está criando seja ADMIN.
Se ele já estava entre os membros, manter ou elevar seu papel para ADMIN.
