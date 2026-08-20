# Documentação dev friendly

Esta área reúne a documentação técnica e objetiva para desenvolver, executar,
testar e operar o projeto. O código e os nomes dos identificadores continuam
sendo a referência da implementação; estes documentos registram como usá-los
com segurança.

## Como rodar o projeto localmente

### Requisitos

- PHP 8.2 ou superior;
- Composer;
- os ativos próprios são publicados pelo Composer e não exigem Node.js ou npm;
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

Depois, aplique as migrações:

```sh
php artisan migrate
```

### Executar a aplicação

Em um terminal, caso esteja rodando local:

```sh
php artisan serve
```

Não é rodar `php artisan serve` caso esteja usando um servidor que aponta para a pasta `public/`. O Laravel já contém um `.htaccess` para o Apache. Garanta que o servidor tenha permissão de escrita em `storage/` e `bootstrap/cache/`.

Como a configuração padrão usa a fila `database`, execute o worker em outro
terminal quando precisar validar e-mails ou outros trabalhos assíncronos. A
geração de miniaturas ocorre dentro da requisição de upload e não depende do
worker:

```sh
php artisan queue:work database --queue=default --sleep=3 --tries=4 --timeout=60
```

Você pode criar uma configuração de supervisão para o worker, mas não é necessário para desenvolvimento local.

O módulo de Arquivos usa armazenamento privado. Não execute
`php artisan storage:link` para esses Arquivos. Garanta que `storage/` e
`bootstrap/cache/` tenham permissão de escrita.

## Arquitetura e regras técnicas

- [CONTEXT.md](../../CONTEXT.md) — linguagem e modelo de domínio canônicos.
- [AGENTS.md](../../AGENTS.md) — regras de colaboração e manutenção do
  repositório.
- [Rastreador de trabalho](agents/issue-tracker.md) — especificações e
  tickets em `.scratch/`.
- [Glossário de tradução](agents/glossario-traducao.md) — equivalências
  usadas na documentação técnica.
- [Aware Prompt](aware_prompt.md) — contexto técnico para assistentes de IA.
- [Regras de domínio e pontos de aplicação](regras-de-dominio.md) —
  invariáveis, autorização, módulos, ciclos e efeitos assíncronos.
- [Ordenações do sistema](ordenacoes-do-sistema.md) — critérios das listas,
  desempates, tratamento de valores nulos e lacunas sem ordem garantida.
- [Arquivos e Links](arquivos-links/implementar-links-mult-arquivos.md) — detalhes de implementação e compartilhamento de Arquivos e Links.
- [Cores dos Cards](visual/cores_cards.md) — identidade visual por entidade, incluindo projetos, tarefas e reuniões. Segue o padrão da EESC-USP, com cores discretas e consistentes em todas as telas.

## ADRs

ADRs registram decisões difíceis de reverter. Leia os relevantes antes de
alterar o comportamento documentado:

- [Registros de reunião e itens de pauta independentes](adr/0001-registros-de-reuniao-e-itens-de-pauta-independentes.md)
- [Markdown e Menções nos campos textuais](adr/0002-markdown-e-mencoes-nos-campos-textuais.md)
- [Modelo, segurança e compartilhamento de Arquivos](adr/0003-modelo-seguranca-e-compartilhamento-de-arquivos.md)
- [Bibliotecas de front-end por CDN](adr/0004-bibliotecas-frontend-por-cdn.md)
- [Generalizar Menções para entidades do sistema](adr/0005-generalizar-mencoes-para-entidades.md)
- [Notificações de Menções no resumo](adr/0007-notificacoes-de-mencoes-no-resumo.md)
- [Geração síncrona de miniaturas no envio de Arquivos](adr/0006-geracao-sincrona-de-miniaturas.md)

## Implantação e operação

- [Implantação de registros de reunião](implantacao-registros-de-reuniao.md)
  — migrações, validação e reversão protegida.
- [Implantação de Markdown, Arquivos e Menções](../user-friendly/markdown/implantacao.md)
  — checklist curto de publicação, verificação e recuperação.
- [Implantação de acompanhamentos](email/implantacao-observacoes.md) —
  tabelas, eventos e processamento dos resumos por e-mail.
- [Fluxo de notificações de Menções](email/fluxo-notificacoes-de-mencoes.md) —
  criação, digest e desativação do acompanhamento geral.
- [Índice de Menções e auditoria](operacao/indice-de-mencoes-e-auditoria.md)
  — reconstrução, limpeza, fila e observabilidade.
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
