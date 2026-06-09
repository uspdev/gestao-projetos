# Sistema de Gestão de Projetos USP

## Sobre o projeto

Sistema interno construído em Laravel que organiza e centraliza projetos e tarefas, com base pronta para evoluir para reuniões e outras features do roadmap.

## Documentação

- [MVP](docs/MVP.md) — Escopo e decisões que orientaram o produto mínimo viável do sistema.
- [Roadmap](docs/roadmap.md) — Funcionalidades planejadas, prioridades futuras e entregas já implementadas.
- [Permissões](docs/permissoes.md) — Regras de acesso, papéis, policies, subprojetos e herança de permissões.
- [Aware Prompt](docs/aware_prompt.md) — Aware prompt de contexto para assistentes de IA externos; NÃO é um arquivo do tipo `AGENTS.md`.

## Características

- Gestão de projetos com descrição, status, fases, tags, membros e configurações próprias.
- Tipos de projeto com ativação granular de módulos, como tarefas, reuniões e fases.
- Projetos organizacionais e subprojetos, com vínculo controlado e herança configurável de acesso.
- Gestão de tarefas com prioridade, status, datas, tags e múltiplos responsáveis.
- Tarefas em tabela, cartões ou Kanban, com buscas e filtros por contexto.
- Gestão de reuniões vinculadas a múltiplos projetos, com pauta, notas e ciclo de status.
- Itens de pauta associados a projetos ou tarefas, com ordenação e registros individuais.
- Comentários em projetos, tarefas, reuniões e itens de pauta.
- Dashboard pessoal com projetos fixados, reuniões agendadas e tarefas atribuídas.
- Buscas contextuais de projetos, subprojetos e tarefas.
- Gestão de membros integrada ao Replicado por número USP (`codpes`).
- Autenticação institucional pela Senha Única.
- Controle de acesso com roles, policies, visibilidade e herança de permissões.
- Notificações assíncronas por e-mail para eventos relevantes do sistema.
- Auditoria backend de alterações em entidades e relacionamentos, ainda sem interface de consulta.
- Navegação contextual entre projetos, subprojetos, tarefas e módulos.

## Funcionamento

Cada projeto possui membros locais com os papéis `ADMIN`, `CONTRIBUTOR` ou
`VIEWER`. As policies combinam essas roles com a visibilidade, os módulos ativos
e, nos subprojetos, a configuração de herança. Usuários elegíveis podem ingressar
ativamente em um subprojeto.

Tarefas pertencem a um projeto e podem ter vários responsáveis, enquanto
reuniões podem abranger vários projetos e organizar uma pauta formada por
projetos e tarefas. Projetos, tarefas, reuniões e itens de pauta aceitam
comentários dentro das permissões do usuário. Alterações de status são realizadas
no próprio recurso e refletidas nas visualizações em lista, cartões e Kanban.

O dashboard pessoal consulta os vínculos diretos do usuário para apresentar
projetos fixados, reuniões pendentes e tarefas atribuídas. Buscas e filtros atuam
sobre cada contexto, e as preferências de visualização são mantidas na sessão.
Ações relevantes disparam e-mails enfileirados, enquanto a auditoria registra no
backend o autor, o evento e os valores alterados. As rotas de projeto usam
slugs, e a inclusão de membros consulta o Replicado quando disponível.

## Requisitos

- Servidor Linux (Ubuntu ou Debian).
- PHP 8.2+.
- Composer.
- Git.
- Banco de dados compatível com Laravel (ex: MariaDB/MySQL ou SQLite).
- Credenciais de Senha Única.
- Acesso ao Replicado para busca de pessoas e cadastro de membros por codpes.

## Instalação

```sh
git clone git@github.com:uspdev/gestao-projetos.git
cd gestao-projetos
composer install
cp .env.example .env
php artisan key:generate
```

Configure o `.env` (incluindo `SENHAUNICA_CALLBACK_ID`) e rode:

```sh
php artisan migrate
```

## Configuração em ambiente de produção

- Aponte o servidor web para a pasta `public/`.
- Defina `APP_ENV=production` e `APP_DEBUG=false`.
- Garanta permissão de escrita em `storage/` e `bootstrap/cache/`.

- Configurar email e supervisor para envio de fila de emails.

## Configuração em ambiente de desenvolvimento

```sh
php artisan serve
```

## Histórico

- Projeto iniciado em 2026 com foco no MVP de projetos e tarefas.

## Detalhamento técnico

- Laravel 12 com Eloquent.
- Padrão FormRequest + Action para casos de uso.
- Tags com `spatie/laravel-tags`.
- Slugs e auditoria automática via traits.

## Changelog

01/06/2026

- Release ciclo 2: sub-projetos, tipos de projetos, módulos ativaveis, módulo Fases

30/04/2026

- Release do MVP.

03/2026

- Setup inicial da arquitetura do MVP (Projetos, Tarefas e Usuários).
- Configuração de validações dinâmicas com Form Requests e Action Pattern.
