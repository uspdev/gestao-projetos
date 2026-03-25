***
# MVP (Minimum Viable Product)

## O núcleo do sistema focado em resolver a organização básica de projetos, tarefas e o acompanhamento de reuniões.
> Este documento detalha as funcionalidades do sistema de gestão de projetos, especificamente o Produto Mínimo Viável (MVP) — essencial para o lançamento.
---

## 1. Gestão de Projetos (Projects)

> O sistema permite a criação e centralização de projetos, vinculando-os aos usuários responsáveis da equipe. Cada projeto possui um status de acompanhamento ('DEVELOPMENT', 'PRODUCTION', 'MIGRATED'...) para facilitar a identificação de sua etapa atual no ciclo de vida do desenvolvimento.

* **Implementação:** Tabela `projects` com CRUD padrão no Laravel e tabela pivô `project_user`.
* **Prós:** Simplicidade inicial; resolve o problema imediato de organização.
* **Contras:** Sem a tabela `documents` no MVP, o contexto detalhado do projeto precisará ficar temporariamente na descrição básica.
---

## 2. Gestão de Tarefas (Tasks)

> Fornece um sistema de cards para mapear e acompanhar o trabalho. Cada tarefa conta com definição de prioridade, status ('TO_DO', 'IN_PROGRESS', 'IN_REVIEW'...), datas de início e entrega, além de labels ('FEATURE','FIX','DOC'...).

* **Implementação:** Tabela `tasks` vinculadas aos projetos, com tabela pivô `task_user` para atribuição de múltiplos responsáveis.
* **Prós:** Permite acompanhamento granular do progresso da equipe.
* **Contras:** Sem aninhamento (subtasks) no MVP.
---

## 3. Autenticação e Usuários (Users)

> Controle de acesso seguro e unificado, permitindo que os usuários acessem a plataforma utilizando as credenciais da rede da instituição. Elimina a necessidade de criação de novos cadastros manuais ou gerenciamento de múltiplas senhas pelos colaboradores.

* **Implementação:** Utilização do pacote `senha-unica-socialite`.
* **Prós:** O usuário não precisa decorar uma nova senha; a gestão de identidades é 'terceirizada' e segura.

***
