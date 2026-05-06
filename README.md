
# Sistema de Gestão de Projetos USP

## Sobre o projeto

Sistema interno construído em Laravel que organiza e centraliza projetos e tarefas, com base pronta para evoluir para reuniões e outras features do roadmap.

Documentação:
- [docs/MVP.md](docs/MVP.md)
- [docs/roadmap.md](docs/roadmap.md)

## Características

- Gestão de projetos com status, descrição e membros.
- Gestão de tarefas com prioridade, status, datas e responsáveis.
- Visão de tarefas em lista ou kanban (por projeto e por usuário), com filtro de concluídas.
- Tags por tipo para classificação de projetos e tarefas.
- Gestão de membros com busca de pessoas via Replicado (codpes).
- Autenticação por Senha Única Socialite.
- Controle de acesso por roles e policies.
- Arquitetura preparada para expansão.

## Funcionamento

Projetos têm membros com papéis (OWNER, CONTRIBUTOR, VIEWER). Tarefas pertencem a projetos e podem ser atribuídas a múltiplos usuários. A busca de membros usa o Replicado quando disponível. O acesso é controlado por policies e as rotas de projeto usam slug.

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

30/04/2026

- Release do MVP.

03/2026

- Setup inicial da arquitetura do MVP (Projetos, Tarefas e Usuários).
- Configuração de validações dinâmicas com Form Requests e Action Pattern.
