# Sistema de Gestão de Projetos

Aplicação interna da USP, desenvolvida em Laravel, para apoiar o planejamento, a organização e o
acompanhamento das atividades realizadas pelas equipes da universidade.

O sistema centraliza a gestão de projetos, subprojetos, tarefas, reuniões, arquivos, comentários e membros, permitindo que diferentes equipes acompanhem seu trabalho em um único ambiente. Além da organização das atividades, a aplicação oferece controle de acesso, notificações e integração com serviços institucionais da USP.

## Documentação

A documentação está organizada por público:

### Para usuários

- [Documentação user-friendly](docs/user-friendly/README.md) — visão do
  produto, regras de uso e guias dos principais recursos.
- [Permissões](docs/user-friendly/permissoes.md) — papéis, visibilidade,
  membros e acesso a projetos e subprojetos.
- [Reuniões](docs/user-friendly/reunioes.md) — criação, pauta, registros e
  exportação.
- [Arquivos](docs/user-friendly/arquivos.md) — envio, acesso, Menções e
  compartilhamento com reuniões.
- [Markdown](docs/user-friendly/markdown/README.md) — formatação, Menções e
  pré-visualização.
- [Notas de versão](docs/user-friendly/releases/README.md) — histórico das
  versões publicadas.

### Para desenvolvimento e operação

- [Documentação dev-friendly](docs/dev-friendly/README.md) — instalação local,
  testes, implantação e operação.
- [CONTEXT.md](CONTEXT.md) — glossário e linguagem canônica do domínio.
- [Regras de domínio](docs/dev-friendly/regras-de-dominio.md) — invariantes,
  autorização, módulos e efeitos assíncronos.
- [ADRs](docs/dev-friendly/adr/) — decisões de arquitetura e comportamento.
- [Aware Prompt](docs/dev-friendly/aware_prompt.md) — visão técnica para
  manutenção assistida.

## Características

- Gestão de projetos com tipos, fases, tags, membros e subprojetos.
- Módulos configuráveis por projeto, incluindo tarefas e reuniões.
- Tarefas com status, prioridade, prazos, responsáveis e visualização em
  tabela, cartões ou Kanban.
- Reuniões vinculadas a projetos, com pauta, registros e itens independentes.
- Comentários, Markdown, Menções e Arquivos privados nos contextos do sistema.
- Dashboard pessoal e buscas contextuais para acompanhar o trabalho.
- Autenticação pela Senha Única e integração com o Replicado para dados de
  usuários da USP.
- Controle de acesso por papéis, visibilidade e herança configurável em
  subprojetos.
- Notificações por e-mail processadas em fila e auditoria das alterações.

## Requisitos

- PHP 8.2 ou superior.
- Composer.
- Git.
- SQLite, MariaDB ou MySQL compatível com a configuração do Laravel.
- Credenciais de Senha Única.
- Acesso ao Replicado para busca de pessoas e cadastro de membros por codpes.

## Instalação

```sh
git clone https://github.com/uspdev/gestao-projetos.git
cd gestao-projetos
composer install
cp .env.example .env
php artisan key:generate
```

Configure o banco, a Senha Única e o Replicado no `.env`.

```sh
php artisan migrate
```

A configuração padrão usa a fila `database`. Em outro terminal, execute o
worker para processar e-mails e outros trabalhos assíncronos:

```sh
php artisan queue:work database --queue=default
```

## Changelog

- [Versão 1.1](docs/user-friendly/releases/1.1.md) — registros completos de
  reuniões, duplicação, Markdown, Arquivos, Menções e acompanhamento por
  e-mail.
- [Versão 1.0](docs/user-friendly/releases/1.0.md) — versão-base com projetos,
  tarefas, reuniões, envio de e-mails e dashboards.
- [MVP](docs/user-friendly/releases/MVP.md) — primeira entrega, focada em
  projetos, tarefas e autenticação institucional.
