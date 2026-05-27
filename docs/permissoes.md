## 1. Níveis de permissão por projeto

> O acesso aos projetos é baseado na role do usuário dentro da tabela pivô `project_user`, com três níveis ativos no MVP.

* **Admin:** pode gerenciar membros, excluir projetos e executar todas as ações de colaborador dentro do projeto.
* **Colaborador:** pode criar e editar tarefas, reuniões e dados do projeto, além de comentar nos recursos associados.
* **Visualizador:** pode visualizar projetos e entidades relacionadas, mas não pode criar nem editar conteúdo.

#### 1.1 Roles válidas são `ADMIN`, `CONTRIBUTOR` e `VIEWER`, mapeadas para labels de UI (Admin, Colaborador, Visualizador).

---

## 2. Escopo global (role admin)

> Existe um nível global de acesso baseado em roles/permissões do sistema (Spatie Permission), independente da role do projeto.

* **Quem possui:** usuários com role `admin` ou permissão `admin` no sistema.
* **Rotas protegidas:** a área administrativa usa middleware `can:admin`.

---

## 3. Gestão de membros e roles

> A administração de membros é centralizada no módulo de configurações do projeto e restrita a admins do projeto.

* **Adicionar membros:** apenas admins do projeto podem incluir usuários e definir a role inicial.
* **Remover membros:** apenas admins do projeto podem remover; o último admin do projeto não pode ser removido.
* **Alterar role:** apenas admins do projeto; não é possível rebaixar o último admin do projeto.
* **Criação de projeto:** o usuário criador entra automaticamente como `ADMIN` no projeto.

---

## 4. Projetos

> As permissões de projeto determinam acesso à visualização, edição e configurações gerais.

* **Visualizar:** requer ser visualizador do projeto, incluindo herança para subprojetos quando configurada.
* **Criar:** permitido apenas para usuários em `senhaunica.estagiario`, `senhaunica.docente` ou `senhaunica.servidor`.
* **Editar configurações:** qualquer `CONTRIBUTOR` (ou `ADMIN`) pode atualizar nome, slug, descrição, status, tags, visibilidade.
* **Excluir:** somente `ADMIN` do projeto pode deletar projetos.

---

## 5. Tarefas (Tasks)

> O módulo de tarefas é controlado por policy e pela habilitação do módulo no projeto.

* **Visualizar e listar:** requer projeto com módulo `tasks` ativo e usuário com acesso de visualização ao projeto.
* **Criar:** requer `CONTRIBUTOR` no projeto e módulo `tasks` ativo.
* **Editar:** permitido para `ADMIN` do projeto ou usuário atribuído na tarefa, desde que a tarefa não esteja bloqueada.
* **Excluir:** permitido para `ADMIN` do projeto ou criador da tarefa, desde que a tarefa não esteja bloqueada.
* **Atribuir responsáveis:** permitido para `ADMIN` do projeto ou criador da tarefa; o usuário atribuído deve ser colaborador do projeto.

---

## 6. Reuniões (Meetings)

> O módulo de reuniões depende de ativação por projeto e valida regras adicionais para vínculos entre projetos.

* **Visualizar:** requer módulo `meetings` ativo e permissão de visualização no projeto da rota (ou no projeto pai quando o alvo é subprojeto).
* **Criar/editar/excluir:** requer `CONTRIBUTOR` no projeto e módulo `meetings` ativo.
* **Projetos vinculados:** uma reunião pode estar ligada a vários projetos, e todos precisam ter o módulo de reuniões habilitado.
* **Itens de pauta:** somente colaboradores podem adicionar/remover itens, e isso é bloqueado quando a reunião está `COMPLETED`.
* **Notas de itens:** só podem ser atualizadas por colaboradores enquanto a reunião não estiver concluída.

---

## 7. Comentários

> Comentários seguem a permissão do recurso comentado (projeto, tarefa ou reunião).

* **Criar:** requer permissão `comment` no recurso comentado.
* **Visualizar:** depende de permissão `view` no recurso comentado.
* **Editar:** apenas o autor.
* **Remover:** apenas o autor.


---

## 8. Subprojetos e herança de permissões

> A relação de subprojetos impõe regras específicas de vínculo e herança de acesso.

* **Vínculo permitido:** apenas projetos organizacionais podem ter subprojetos; o subprojeto precisa ser projeto raiz e não pode ter subprojetos.
* **Restrições de tipo:** projetos organizacionais não podem ser subprojetos de outros projetos organizacionais.
* **Admin em comum:** o vínculo só é permitido se houver pelo menos um admin em comum entre pai e subprojeto.
* **Quem pode vincular/desvincular:** vincular exige `ADMIN` do projeto pai e permissão de update no subprojeto; desvincular exige `ADMIN` do projeto pai ou `ADMIN` do subprojeto.
* **Herança:** `READ` herda apenas visualização; `FULL` herda visualização e colaboração; `NONE` não herda nada.
* **Regra de subprojeto:** membros que são `ADMIN` no projeto pai devem permanecer como `ADMIN` nos subprojetos vinculados. Por isso, não é permitido rebaixar sua role no subprojeto para `CONTRIBUTOR` ou `VIEWER`. Caso o usuário já possua outra role no subprojeto antes do vínculo, ele poderá ser promovido para `ADMIN`, mas não rebaixado posteriormente.
