# Implantação de Markdown, Arquivos e Menções — versão alfa

> **Documento alfa:** esta versão cobre as necessidades operacionais imediatas
> do renderizador Markdown, do EasyMDE, da pré-visualização oficial e da base
> privada de Arquivos. Compartilhamentos, Menções e suas interfaces serão
> acrescentados quando os respectivos tickets forem implementados. Este
> documento não autoriza nem executa a implantação.

## Escopo atual

A entrega atual acrescenta ativos JavaScript e CSS compilados localmente por
Laravel Mix. O EasyMDE e o `highlight.js` são dependências npm locais e não
devem ser substituídos por CDN.

O arquivo-fonte `resources/js/app.js` não é servido diretamente ao navegador.
O Laravel Mix precisa gerar, no mínimo:

- `public/js/app.js`;
- `public/css/app.css`;
- `public/mix-manifest.json`.

Esses arquivos compilados não são versionados no repositório. Portanto, uma
nova release não deve ser publicada sem executar a compilação ou sem receber
os artefatos produzidos por um pipeline de build.

## Requisito do ambiente de build

O ambiente responsável por preparar a release precisa ter Node.js e npm
disponíveis. Eles não precisam permanecer instalados no servidor de execução
quando um pipeline externo gerar e entregar os ativos compilados.

As ferramentas de compilação estão registradas em `devDependencies`. Não use
`npm ci --omit=dev`, `npm install --production` nem configuração equivalente
durante o build, pois isso removeria Laravel Mix, EasyMDE e `highlight.js` antes
da compilação.

O módulo de Arquivos usa `spatie/laravel-medialibrary`, já registrado em
`composer.lock`. Não execute `composer update` durante a implantação. O
`composer install` permanece apenas como parte do procedimento normal de
preparação da aplicação.

## Arquivos: identidade, nomes e armazenamento

Cada Arquivo pertence exclusivamente a um Projeto, Tarefa ou Reunião. O disk
`files` é privado e usa, inicialmente, `storage/app/files`; ele não depende de
`storage:link` nem fornece URLs públicas diretas.

| Campo da aplicação | Coluna persistida | Exemplo | Finalidade |
|---|---|---|---|
| `original_name` | `media.original_name` | `relatório final.PNG` | Nome informado pelo navegador no envio, preservado como proveniência. |
| `uuid_name` | `media.file_name` | `550e8400-e29b-41d4-a716-446655440000.png` | Nome físico opaco no armazenamento privado. |
| `uuid` | `media.uuid` | `550e8400-e29b-41d4-a716-446655440000` | Identidade pública estável para futuras rotas e Referências de arquivo. |
| `display_name` | `media.name` | `relatório final` | Nome exibido, que poderá ser renomeado sem alterar o conteúdo ou a proveniência. |

`display_name` e `uuid_name` são atributos da aplicação. As colunas `name` e
`file_name` pertencem ao contrato técnico da Media Library e devem permanecer
restritas à sua integração. Fora dessa fronteira, use os atributos da
aplicação.

`original_name`, `uuid_name`, `uuid`, conteúdo e Proprietário do arquivo são
imutáveis. Uma renomeação altera somente `display_name`. O Nome original do
arquivo não deve ser usado para caminhos, URLs ou cabeçalhos de download.

## Preparação dos ativos da release

No código exato que será publicado, execute:

```sh
npm ci
npm run production
```

`npm ci` instala exatamente a árvore registrada em `package-lock.json` e falha
se o manifesto e o lockfile estiverem divergentes. `npm run production` gera os
ativos minificados em `public/`.

A compilação pode ocorrer de duas formas:

1. **Na preparação da release no ambiente de produção:** execute os comandos no
   diretório da nova release antes de torná-la ativa.
2. **Em pipeline de build:** execute os mesmos comandos no pipeline e publique
   junto com a release todo o conteúdo gerado necessário em `public/`.

Em ambos os casos, os ativos precisam corresponder ao mesmo commit do código
PHP e das views. Não reutilize `public/js/app.js` de uma release anterior.

## Validação antes da publicação

Depois da compilação e antes de ativar a release, confirme:

```sh
test -s public/js/app.js
test -s public/css/app.css
test -s public/mix-manifest.json
```

Também valide que `public/mix-manifest.json` contém entradas para `/js/app.js`
e `/css/app.css`.

Se qualquer comando npm ou validação falhar, interrompa a publicação. Não
publique apenas o código PHP: sem o bundle correspondente, os campos continuam
no HTML, mas não recebem o editor nem a pré-visualização no navegador.

## Verificação funcional após a publicação

Com um usuário autenticado e autorizado, verifique ao menos:

1. uma descrição de Projeto ou Tarefa abre o perfil completo do editor;
2. um Comentário abre o perfil compacto;
3. Anotações prévias de Reunião e de item de pauta abrem o perfil completo;
4. Ata e Transcrição continuam como texto simples;
5. a pré-visualização exibe o Markdown após a pausa de digitação;
6. blocos de código recebem realce no navegador;
7. não há requisições a CDN para EasyMDE, `highlight.js` ou dicionários de
   correção ortográfica.

## Recuperação imediata

Esta etapa do editor não cria migrações nem altera dados persistidos. Se os
ativos estiverem ausentes ou incorretos, mantenha a release anterior ativa ou
restaure o conjunto de código e ativos da release anterior. Depois de corrigir
o ambiente de build, gere novamente os ativos a partir do commit pretendido e
repita as validações.

Não tente corrigir uma falha de build com `composer update`, `npm update`, CDN
ou edição manual de arquivos dentro de `public/`.
