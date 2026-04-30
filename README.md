
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

### Senha Única

Cadastre uma nova URL no configurador de senha única da USP utilizando o caminho `https://seu_app/callback`. Guarde o `callback_id` e adicione ao seu arquivo `.env`.

## Problemas e soluções

  * **Erro de Intelephense no VS Code após clonar o projeto:**
    Certifique-se de ter rodado `composer install` e os comandos do IDE Helper descritos na seção de Instalação. O pacote recriará os mapeamentos dinâmicos que o `.gitignore` não rastreia.

## Changelog

03/2026

* Setup inicial da arquitetura do MVP (Projetos, Tarefas e Usuários)
* Configuração de validações dinâmicas com Form Requests e Action Pattern
* Implementação do `barryvdh/laravel-ide-helper` no fluxo de desenvolvimento local
