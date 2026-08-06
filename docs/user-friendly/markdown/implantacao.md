# Implantação de Markdown, Arquivos e Menções

Este é um checklist curto para a implantação. Ele não executa mudanças de
infraestrutura, configura S3, altera e-mails ou substitui os procedimentos
gerais do ambiente.

## Preparação

1. Leia as decisões de [Markdown](../../dev-friendly/adr/0002-markdown-e-mencoes-nos-campos-textuais.md),
   [Arquivos](../../dev-friendly/adr/0003-modelo-seguranca-e-compartilhamento-de-arquivos.md),
   [CDN](../../dev-friendly/adr/0004-bibliotecas-frontend-por-cdn.md) e
   [Menções](../../dev-friendly/adr/0005-generalizar-mencoes-para-entidades.md).
2. Faça cópia consistente do banco e de `storage/app/files`.
3. Confirme que `storage/app/files` é privado e gravável pelo PHP e pelo worker.
4. Configure `upload_max_filesize=100M` e `post_max_size=110M`; o proxy que
   recebe o upload também deve aceitar pelo menos 100 MiB.
5. Confirme a extensão GD com `php --ri gd` e mantenha
   `QUEUE_CONNECTION=database` com um worker supervisionado, por exemplo:
   `php artisan queue:work database --queue=default --sleep=3 --tries=4 --timeout=60`.
   O `.env` não precisa declarar os defaults opcionais `MEDIA_DISK=files`,
   `MEDIA_CONVERSIONS_DISK=files` e `IMAGE_DRIVER=gd`.

## Ordem da publicação

1. Instale dependências travadas e compile somente os ativos próprios:
   `composer install --no-dev --prefer-dist --optimize-autoloader`, `npm ci` e
   `npm run production`.
2. Confira `php artisan migrate:status` e aplique as migrações estruturais da
   release:

   ```sh
   php artisan migrate --path=database/migrations/2026_07_21_090000_create_media_table.php --force
   php artisan migrate --path=database/migrations/2026_07_22_090000_create_meeting_file_shares_table.php --force
   php artisan migrate --path=database/migrations/2026_07_23_090000_create_mentions_table.php --force
   ```

   Se a expansão dos registros de reunião estiver pendente, siga o [guia
   específico](../../dev-friendly/implantacao-registros-de-reuniao.md).
3. Publique o código, limpe os caches com `php artisan optimize:clear` e
   reinicie o worker com `php artisan queue:restart`.
4. Depois de ativar o novo código, converta a descrição legada do Tipo de
   projeto `organizacional` e confira o estado da migração:

   ```sh
   php artisan migrate --path=database/migrations/2026_07_20_120000_convert_organizational_project_type_description_to_markdown.php --force
   php artisan migrate:status
   ```

EasyMDE e `highlight.js` continuam sendo carregados pelo
[jsDelivr](https://cdn.jsdelivr.net/); não copie
essas bibliotecas para o build nem para o servidor. O limite da aplicação é de
100 MiB por Arquivo. Consulte [implantação dos registros de reunião](../../dev-friendly/implantacao-registros-de-reuniao.md)
para a migração específica dessa estrutura.

## Verificação

Execute `php artisan test` e `php artisan dusk`. Em homologação, confirme:

- os cinco campos Markdown salvam, recarregam e exibem conteúdo seguro;
- Ata e Transcrição continuam texto simples;
- Arquivos de Projeto, Tarefa e Reunião são privados, processam miniaturas e
  respeitam os estados `DONE` e `COMPLETED`;
- um upload próximo de 100 MiB é aceito, um maior é rejeitado e o download usa
  `Content-Disposition: attachment` e `X-Content-Type-Options: nosniff`;
- Compartilhamentos de arquivo com Reunião podem ser criados e revogados sem
  alterar a propriedade do Arquivo;
- o autocomplete unificado oferece abas para Usuários, Projetos, Tarefas,
  Reuniões e Arquivos, começa em Usuários, exibe apenas o nome ou título do
  destino, corta nomes visíveis após 50 caracteres e mantém largura fixa entre
  as abas, exigindo seleção explícita sem transformar texto digitado em Menção;
- nas abas Projetos e Tarefas, a busca sem termo mostra apenas resultados
  contextuais; ao digitar, a pesquisa se amplia para Projetos visíveis ou para
  Tarefas ativas de Projetos visíveis com o módulo habilitado, mantendo os
  resultados relacionados primeiro;
- na aba Tarefas, os resultados “Em andamento” aparecem antes dos “Concluídos”,
  cada tarefa possui símbolo colorido de estado e todos os resultados são
  carregados de uma vez dentro de uma área com rolagem interna;
- o índice de Menções e `php artisan mentions:rebuild` são idempotentes, mantêm
  destinos excluídos logicamente recuperáveis, ignoram destinos definitivamente
  ausentes e não geram notificações, e-mails ou tela de backlinks;
- `storage/app/files` não é público e o acesso ao jsDelivr funciona no
  navegador.

## Limites conhecidos

São riscos aceitos: não há antivírus nem cotas; formatos gerais podem ser
enviados; Menções a arquivo podem quebrar após exclusão; fila indisponível deixa
miniaturas pendentes; e uploads aumentam o consumo de armazenamento e backups.
Os detalhes de Arquivos e compartilhamentos estão no [ADR 0003](../../dev-friendly/adr/0003-modelo-seguranca-e-compartilhamento-de-arquivos.md).

## Recuperação

Não execute `php artisan migrate:rollback` nem remova as novas tabelas depois
que houver dados de Arquivos, Compartilhamentos ou Menções. Pare novas gravações,
preserve banco e armazenamento e reative o código anterior sem apagar o esquema
novo. Se necessário, restaure banco e `storage/app/files` da mesma cópia.
