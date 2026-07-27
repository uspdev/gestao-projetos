# Implantação de Markdown, Arquivos e Menções

> **Rascunho:** este guia deve ser revisado e aprovado com o responsável pelo
> deploy antes de ser adotado como procedimento de produção.

Este guia orienta a preparação, a publicação e a validação da release. Execute
as etapas na ordem apresentada e interrompa o deploy se alguma validação falhar.

## Requisitos do ambiente

### Ativos do editor

Execute npm ci e npm run production. A compilação grava os ativos diretamente em public/; esses arquivos não são trazidos pelo Git e nenhuma cópia adicional é necessária.

```sh
# Instala as dependências registradas em package-lock.json.
npm ci

# Gera os ativos otimizados em public/.
npm run production
```

### Armazenamento privado

Os Arquivos ficam em `storage/app/files` e não podem ser servidos diretamente
pelo servidor web. Garanta que o PHP e o processador de fila tenham permissão
de leitura e escrita nesse diretório. Não execute `php artisan storage:link`
para disponibilizá-lo.

```sh
# Cria o diretório privado caso ele ainda não exista.
mkdir -p storage/app/files
```

### Limite de upload

A aplicação aceita um Arquivo por requisição, até 100 MiB. Configure o SAPI web
e todos os proxies para aceitar o corpo multipart completo.

```ini
; Define os limites mínimos no php.ini usado pelo servidor web.
upload_max_filesize = 100M
post_max_size = 110M
max_input_time = 300
```

Para Apache:

```apache
# Permite até 110 MiB por requisição.
LimitRequestBody 115343360
```

Reinicie os serviços alterados. Em homologação, consulte um `phpinfo()`
restrito e temporário para confirmar o arquivo carregado e os valores efetivos
do SAPI web; remova esse diagnóstico após a conferência. O teste definitivo é
o envio próximo de 100 MiB descrito no plano de homologação.

### GD e fila

GD gera as miniaturas e o processador de fila executa esse trabalho de forma
assíncrona. Verifique GD com o mesmo binário PHP usado pelo processador:

```sh
# Falha quando GD ou a função de decodificação não estão disponíveis.
php -r 'exit(extension_loaded("gd") && function_exists("imagecreatefromstring") ? 0 : 1);'

# Exibe a configuração carregada da extensão GD.
php --ri gd
```

Use `QUEUE_CONNECTION=database` e mantenha um worker supervisionado consumindo
a fila padrão:

```sh
# Processa miniaturas e os demais trabalhos da fila padrão.
php artisan queue:work database --queue=default --sleep=3 --tries=3 --timeout=60
```

O supervisor deve reiniciar o processo após falhas e publicações. Monitore
`jobs`, `failed_jobs`, logs e espaço disponível em `storage/app/files`.

## Ordem de implantação

Realize o deploy em janela controlada para impedir gravações durante a cópia de
segurança e a validação do esquema.

### 1. Criar a cópia de segurança

Faça uma cópia consistente do banco e de `storage/app/files`. Registre as
contagens e a descrição que será convertida:

```sql
-- Registra a linha de base antes das migrações.
SELECT COUNT(*) AS project_types FROM project_types;
SELECT COUNT(*) AS meetings FROM meetings;
SELECT COUNT(*) AS meeting_items FROM meeting_items;
SELECT id, slug, description
FROM project_types
WHERE slug = 'organizacional';
```

Não avance sem confirmar que o banco e os binários podem ser restaurados como
um único conjunto.

### 2. Preparar a nova release

No diretório ainda inativo da nova release, instale as dependências travadas
nos arquivos de lock e compile os ativos:

```sh
# Instala as dependências PHP de produção conforme composer.lock.
composer install --no-dev --prefer-dist --optimize-autoloader

# Instala as dependências registradas em package-lock.json.
npm ci

# Gera os ativos otimizados em public/.
npm run production
```

Não execute `composer update` nem `npm update` durante o deploy.

### 3. Executar as migrações estruturais

Confirme que a expansão dos registros de reunião já foi aplicada. Se estiver
pendente, siga
[`implantacao-registros-de-reuniao.md`](implantacao-registros-de-reuniao.md).

```sh
# Exibe as migrações aplicadas e pendentes.
php artisan migrate:status

# Cria a estrutura de Arquivos.
php artisan migrate --path=database/migrations/2026_07_21_090000_create_media_table.php --force

# Cria os Compartilhamentos de arquivo com reunião.
php artisan migrate --path=database/migrations/2026_07_22_090000_create_meeting_file_shares_table.php --force

# Cria o índice derivado de Menções.
php artisan migrate --path=database/migrations/2026_07_23_090000_create_mentions_table.php --force

# Confirma que as migrações estruturais foram aplicadas.
php artisan migrate:status
```

Valide que as tabelas podem ser consultadas antes de ativar o código:

```sql
-- Confirma a criação e a leitura das novas tabelas.
SELECT COUNT(*) FROM media;
SELECT COUNT(*) FROM meeting_file_shares;
SELECT COUNT(*) FROM mentions;
```

### 4. Preparar o processador de fila

Confirme que o worker documentado em **GD e fila** está configurado e ativo. O
usuário do processo deve escrever em `storage/app/files`.

### 5. Ativar a nova release

Ative juntos o código, as views e os ativos compilados. Depois, atualize os
caches e reinicie os workers:

```sh
# Remove caches produzidos pela release anterior.
php artisan optimize:clear

# Gera o cache de configuração da nova release.
php artisan config:cache

# Solicita a reinicialização segura dos workers.
php artisan queue:restart
```

Confirme que o supervisor reiniciou o worker da fila padrão.

### 6. Converter o conteúdo legado

A conversão altera apenas a descrição legada conhecida do Tipo de projeto
`organizacional`. Execute-a depois da ativação do novo renderizador:

```sh
# Converte o HTML legado conhecido para Markdown.
php artisan migrate --path=database/migrations/2026_07_20_120000_convert_organizational_project_type_description_to_markdown.php --force

# Confirma o registro da migração.
php artisan migrate:status
```

Compare a descrição com a linha de base e abra uma tela que a exiba. Descrições
personalizadas não devem ser alteradas.

### 7. Validar a release

Execute integralmente o plano de homologação abaixo. Só encerre a janela
controlada depois que todos os itens forem aprovados.

## Plano de homologação

Registre o usuário, horário, recurso testado e resultado de cada verificação.

### Markdown

Valide descrição de Projeto, descrição de Tarefa, Comentário, Anotações prévias
de Reunião e Anotações prévias do item:

1. abra o editor;
2. edite conteúdo com ênfase, link, código e HTML bruto;
3. confira a pré-visualização;
4. salve e recarregue a página;
5. confirme a formatação e o escape do HTML;
6. confirme que `javascript:` não gera link e que imagens Markdown não são
   incorporadas.

Confirme também que Ata e Transcrição continuam como texto simples e que a
descrição do Tipo de projeto é exibida corretamente.

### Arquivos

1. envie um Arquivo pequeno a Projeto, Tarefa e Reunião;
2. confirme upload em Reunião `COMPLETED`;
3. confirme que Tarefa `DONE` bloqueia upload, renomeação e exclusão;
4. confira processamento, card, Referência de arquivo e download;
5. renomeie e confirme que UUID, Nome original e conteúdo não mudaram;
6. exclua e confirme a remoção de metadados, original e miniatura;
7. envie uma imagem e confirme que a miniatura aparece após o worker processar
   o trabalho.

Envie um Arquivo acima de 99 MiB e até 100 MiB. Ele não deve sofrer `413`,
truncamento ou timeout. Um Arquivo acima de 100 MiB deve ser rejeitado pela
aplicação.

Em um download autorizado, confirme:

- `Content-Disposition: attachment`;
- `X-Content-Type-Options: nosniff`;
- conteúdo idêntico ao enviado;
- ausência de URL pública para `storage/app/files`;
- resposta `404` para UUID inexistente ou sem autorização.

### Reunião multiprojeto

1. crie uma Reunião com dois Projetos e audiências diferentes;
2. compartilhe um Arquivo relacionado e insira sua referência;
3. confirme que todos os visualizadores da Reunião acessam somente o Arquivo
   compartilhado;
4. altere pauta e Projetos vinculados e confirme que o compartilhamento
   permanece;
5. exclua logicamente a origem e confirme resposta `404`;
6. restaure a origem e confirme o retorno do acesso pelo mesmo UUID;
7. remova o compartilhamento e confirme que o Arquivo original permanece.

### Menções

1. busque um membro diretamente vinculado ao contexto;
2. selecione-o e confirme a sintaxe `@[Nome](mention:user:ID)`;
3. salve e confirme uma única relação no índice derivado;
4. remova a elegibilidade e confirme a preservação da Menção histórica;
5. confirme que uma nova Menção inelegível é rejeitada;
6. confirme que nenhuma notificação ou mensagem de e-mail foi criada.

Use o comando abaixo somente para validar uma recuperação em cópia de
homologação. Duas execuções devem produzir contagens estáveis e zero erros:

```sh
# Reconstrói o índice derivado de Menções a partir do Markdown.
php artisan mentions:rebuild
```

Não agende esse comando; o índice é atualizado durante o salvamento normal.

## Testes automatizados

Execute os testes no ambiente preparado para a release antes de iniciar o
deploy:

```sh
# Executa as suítes unitária e HTTP.
php artisan test

# Executa os fluxos de navegador.
php artisan dusk
```

Os testes Dusk exigem navegador, ChromeDriver compatível, banco isolado e
servidor da aplicação. Não publique a release se algum teste falhar.

## Recuperação

Não execute `migrate:rollback` nem remova as novas tabelas depois que houver
Arquivos, Compartilhamentos ou Menções. Essas ações podem causar perda de dados.

Se o código falhar após as migrações:

1. interrompa novas gravações;
2. preserve o banco e `storage/app/files`;
3. pare o worker da fila;
4. reative o código e os ativos da release anterior sem remover as novas
   tabelas;
5. investigue e publique uma correção progressiva.

Se houver corrupção, restaure banco e armazenamento a partir da mesma cópia
consistente. `php artisan mentions:rebuild` recupera apenas o índice de Menções;
ele não recupera Markdown nem Arquivos.

## Riscos operacionais

Considere estes riscos no monitoramento e no dimensionamento do ambiente:

- não há antivírus nem certificação de segurança pelo MIME;
- não há cotas por usuário nem por Proprietário do arquivo;
- Referências de arquivo podem quebrar após exclusão ou revogação de acesso;
- fila indisponível mantém miniaturas pendentes;
- uploads de 100 MiB consomem rede, disco e espaço temporário;
- imagens consomem CPU e memória durante a geração da miniatura;
- banco e `storage/app/files` precisam de cópias de segurança consistentes.
