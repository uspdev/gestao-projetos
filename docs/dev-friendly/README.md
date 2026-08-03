# Documentação dev friendly

Esta área reúne a documentação técnica e objetiva para desenvolver, executar,
testar e operar o projeto. O código e os nomes dos identificadores continuam
sendo a referência da implementação; estes documentos registram como usá-los
com segurança.

## Como rodar o projeto localmente

### Requisitos

- PHP 8.2 ou superior;
- Composer;
- Node.js e npm para compilar os ativos próprios;
- SQLite, MariaDB ou MySQL compatível com a configuração do Laravel;
- credenciais da Senha Única e, quando necessário, acesso ao Replicado.

### Instalação

Na raiz do repositório:

```sh
composer install
cp .env.example .env
php artisan key:generate
```

Configure o `.env` com o banco, `SENHAUNICA_CALLBACK_ID`, credenciais da Senha
Única e os dados do Replicado quando o ambiente precisar deles. A configuração
copiada usa SQLite por padrão e o repositório já contém `database/database.sqlite`.

Depois, aplique as migrações e compile os ativos próprios:

```sh
php artisan migrate
npm ci
npm run production
```

EasyMDE e `highlight.js` são carregados pelo navegador via CDN; o build local
gera somente os JavaScripts e estilos próprios da aplicação.

### Executar a aplicação

Em um terminal:

```sh
php artisan serve
```

Como a configuração padrão usa a fila `database`, execute o worker em outro
terminal quando precisar validar e-mails, miniaturas ou outros trabalhos
assíncronos:

```sh
php artisan queue:work database --queue=default --sleep=3 --tries=4 --timeout=60
```

O módulo de Arquivos usa armazenamento privado. Não execute
`php artisan storage:link` para esses Arquivos. Garanta que `storage/` e
`bootstrap/cache/` tenham permissão de escrita.

### Testes

```sh
php artisan test
php artisan dusk
```

Os testes Dusk exigem um navegador e o driver compatível disponíveis no
ambiente. Testes HTTP e unitários usam o ambiente `testing` configurado em
`phpunit.xml`.

## Arquitetura e regras técnicas

- [CONTEXT.md](../../CONTEXT.md) — linguagem e modelo de domínio canônicos.
- [AGENTS.md](../../AGENTS.md) — regras de colaboração e manutenção do
  repositório.
- [Rastreador de trabalho](agents/issue-tracker.md) — especificações e
  tickets em `.scratch/`.
- [Glossário de tradução](agents/glossario-traducao.md) — equivalências
  usadas na documentação técnica.
- [Aware Prompt](aware_prompt.md) — contexto técnico para assistentes de IA.

## ADRs

ADRs registram decisões difíceis de reverter. Leia os relevantes antes de
alterar o comportamento documentado:

- [Registros de reunião e itens de pauta independentes](adr/0001-registros-de-reuniao-e-itens-de-pauta-independentes.md)
- [Markdown e Menções nos campos textuais](adr/0002-markdown-e-mencoes-nos-campos-textuais.md)
- [Modelo, segurança e compartilhamento de Arquivos](adr/0003-modelo-seguranca-e-compartilhamento-de-arquivos.md)
- [Bibliotecas de front-end por CDN](adr/0004-bibliotecas-frontend-por-cdn.md)
- [Generalizar Menções para entidades do sistema](adr/0005-generalizar-mencoes-para-entidades.md)

## Implantação e operação

- [Implantação de registros de reunião](implantacao-registros-de-reuniao.md)
  — migrações, validação e reversão protegida.
- [Implantação de Markdown, Arquivos e Menções](implantacao-markdown-arquivos-mencoes.md)
  — build, migrações, fila, homologação e recuperação.
- [Implantação de acompanhamentos](email/implantacao-observacoes.md) —
  tabelas, eventos e processamento dos resumos por e-mail.
- [Notificações por e-mail](../user-friendly/email/notificacoes.md) — contrato observável dos
  eventos e destinatários.

## Fluxos técnicos

- [Fluxo de Markdown](markdown/diagrama-markdown.md) — edição,
  pré-visualização, renderização segura, índice de Menções e Arquivos.
- [Roadmap concluído](../user-friendly/roadmap-concluded.md) — escopo factual das entregas,
  útil para evitar reimplementar uma proposta já resolvida de outra forma.

Mudanças de domínio devem atualizar o ADR correspondente ou registrar um novo
ADR. Mudanças de comportamento devem incluir testes proporcionais ao risco e
manter os documentos desta área objetivos e verificáveis.
