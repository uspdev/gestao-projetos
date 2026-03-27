# Sistema interno USP para gerenciamento de projetos

## Documentação

- MVP: [docs/MVP.md](docs/MVP.md)
- Road Map: [docs/roadmap.md](docs/roadmap.md)
- IDE Helper: [Tipagem e Autocompletação (IDE Helper)](docs/ide_helper.md)

-----

# Sistema de Gestão de Projetos USP

Sistema interno construído em Laravel que auxilia na organização, centralização e acompanhamento de projetos, tarefas e reuniões, desenhado para atender tanto o corpo de desenvolvedores quanto o setor administrativo.

## Funcionalidades

  * Gera e gerencia Projetos com ciclo de vida.
  * Fornece um sistema para mapear Tarefas com prioridades, datas, labels e status
  * Autenticação unificada utilizando as credenciais da rede da instituição (Senha Única Socialite)
  * Estrutura arquitetural preparada para expansão contínua.

## Documentação

  - [MVP - Minimum Viable Product](https://www.google.com/search?q=docs/MVP.md)
  - [Roadmap - Features Futuras](https://www.google.com/search?q=docs/roadmap.md)
  - [Tipagem e Autocompletação (IDE Helper)](https://www.google.com/search?q=docs/ide_helper.md)

## Requisitos

Aplicação Laravel padrão (PHP 8.2+).

## Instalação

### Básico

```sh
git clone [git@github.com:uspdev/gestao-projetos.git]
composer install

# Configure o .env conforme a necessidade
cp .env.example .env
php artisan key:generate
```

### Configuração para Desenvolvimento (IDE Helper)

O projeto utiliza o [`barryvdh/laravel-ide-helper`](docs/ide_helper.md) para garantir tipagem forte e autocompletar na IDE. Esse pacote atua em duas frentes distintas e ambas já estão automatizadas:

**1. Tipagem de Classes e Facades (Framework)**
Logo após clonar o repositório pela primeira vez, para que sua IDE reconheça as classes nativas do Laravel, rode:
```sh
php artisan ide-helper:generate
php artisan ide-helper:meta
```
> **Automação:** No dia a dia, não é necessário repetir esses dois comandos manualmente. Sempre que ao rodar `composer update` para baixar ou atualizar pacotes, o `composer.json` se encarregará de regerar esses arquivos de mapeamento ocultos.

**2. Tipagem de Banco de Dados (Models)**
Sempre que você alterar o esquema do banco, rode:
```sh
php artisan migrate
```
> **Automação:** Foi implementado um *listener* no `AppServiceProvider`. Ao finalizar a migração, ele automaticamente roda o comando `ide-helper:models` e atualiza as propriedades (`@property`) no topo das classes em `app/Models/`.


### Senha Única

Cadastre uma nova URL no configurador de senha única da USP utilizando o caminho `https://seu_app/callback`. Guarde o `callback_id` e adicione ao seu arquivo `.env`.

### Banco de dados

  * **DEV**

    Cria as tabelas, roda os seeds e aciona automaticamente a reescrita do IDE Helper nos Models:
    `php artisan migrate:fresh --seed`

  * **Produção**

    `php artisan migrate --force`

## Problemas e soluções

  * **Erro de Intelephense no VS Code após clonar o projeto:**
    Certifique-se de ter rodado `composer install` e os comandos do IDE Helper descritos na seção de Instalação. O pacote recriará os mapeamentos dinâmicos que o `.gitignore` não rastreia.

## Changelog

03/2026

* Setup inicial da arquitetura do MVP (Projetos, Tarefas e Usuários)
* Configuração de validações dinâmicas com Form Requests e Action Pattern
* Implementação do `barryvdh/laravel-ide-helper` no fluxo de desenvolvimento local