# Recursos granulares na API de leitura

**Status:** aceito

A API exporá Projeto, Reuniões, Tarefas e Arquivos como recursos de leitura
separados sob `/api/projects/{project}`. A separação permite descobrir
coleções com filtros e recuperar individualmente o conteúdo completo de cada
recurso sem concentrar todo o contexto do Projeto em uma única resposta.

As coleções `/meetings`, `/tasks` e `/files` terão filtros, ordenação e
paginação. Os detalhes `/meetings/{meeting}` e `/tasks/{task}` retornarão um
único recurso JSON, sem filtros nem paginação. Já `/files/{uuid}` entregará
diretamente o conteúdo binário do Arquivo, com `Content-Type`,
`Content-Length` e um nome seguro em `Content-Disposition`. A resposta usará
`Content-Disposition: attachment` e `X-Content-Type-Options: nosniff`,
forçando o download inclusive de imagens e PDFs e evitando a renderização de
conteúdo ativo no domínio da aplicação. Não haverá um endpoint separado de
metadados, conteúdo ou miniatura. Toda consulta confirmará que o recurso
pertence ou está vinculado ao Projeto da URL e que esse Projeto é o owner da
Chave de API autenticada.

A ordenação das coleções será fixa, sem parâmetros `sort` ou `direction`:
Reuniões por `scheduled_at` e identificador decrescentes, Tarefas por
`updated_at` e identificador decrescentes e Arquivos por data de upload e
identificador decrescentes. O segundo critério tornará a paginação estável
quando houver datas iguais.

Projetos, Tarefas e Reuniões excluídos logicamente não serão consultáveis pela
API: ficarão fora das coleções e seus detalhes responderão `404`. Não haverá
`with_deleted` nem endpoints de lixeira. Arquivos pertencentes a um recurso
excluído também não serão expostos por esse vínculo; a própria coleção e o
download de Arquivos só considerarão caminhos de acesso ainda válidos. A
exclusão lógica de um Projeto tornará todas as suas rotas indisponíveis e
revogará suas Chaves de API conforme o ADR 0009.

As três coleções usarão `page` e `per_page` e preservarão o envelope padrão do
paginador do Laravel: itens em `data`, URLs de navegação em `links` e estado e
contagens da paginação em `meta`, incluindo página atual, última página,
quantidade por página e total de registros.

O detalhe do Projeto retornará identificador, slug, nome, descrição integral,
estado com valor e rótulo, resumos de tipo, fase e Projeto pai, tags, lista de
módulos habilitados, URL web e timestamps. Ele não incorporará membros,
subprojetos, Arquivos, Tarefas ou Reuniões. O campo redundante
`modules.tasks_enabled` não integrará a resposta; consumidores consultarão `tasks` e
`meetings` diretamente na lista `modules.enabled`.

Uma Reunião diretamente vinculada ao Projeto será retornada integralmente,
mesmo quando também estiver vinculada a outros Projetos. Isso inclui toda a
Pauta e referências a Projetos ou Tarefas externos ao escopo da chave, seguindo
a mesma visibilidade da interface web; essas referências não concederão acesso
aos endpoints de detalhe dos recursos mencionados. Para chaves de subprojetos,
o vínculo da Reunião apenas com o Projeto pai não será suficiente: a API exigirá
vínculo direto com o Projeto da URL.

A coleção de Reuniões retornará metadados para descoberta: identificador,
título, estado com valor e rótulo, data agendada, local, resumos dos Projetos
vinculados, timestamps e URL web. O detalhe acrescentará Anotações prévias,
Ata e Transcrição integrais, a Pauta ordenada com suas anotações e referências
resumidas e os comentários ativos em ordem cronológica, identificando o autor
somente pelo nome. Arquivos não serão incorporados nessa resposta porque terão
recurso e filtros próprios. A coleção não repetirá os registros textuais
longos, especialmente a Transcrição, cujo limite atual é 100.000 caracteres.

A coleção de Reuniões aceitará `status[]`, `scheduled_from`, `scheduled_to`,
`search` e `per_page`. A busca abrangerá título e local; as datas serão limites
inclusivos em ISO 8601 e intervalos inválidos responderão `422`. Sem filtros,
serão retornadas todas as Reuniões não excluídas diretamente vinculadas ao
Projeto, inclusive concluídas, por `scheduled_at` e identificador decrescentes.
A paginação usará 20 itens por padrão e aceitará de 1 a 100.

A coleção de Tarefas preservará a descrição integral em todos os itens e
aceitará `status[]`, `priority[]`, `due_from`, `due_to`, `tag[]`, `search` e
`per_page`. O intervalo de vencimento será inclusivo em `YYYY-MM-DD`, as tags
serão filtradas por slug com correspondência a qualquer valor e a busca
abrangerá título e descrição. Sem filtros, serão retornadas todas as Tarefas
não excluídas, inclusive concluídas, por atualização e identificador
decrescentes. Não haverá filtro por responsável enquanto a API não expuser um
identificador estável de usuário.

Quando o módulo correspondente estiver desativado no Projeto, os endpoints de
Tarefas responderão `409 tasks_module_disabled` e os de Reuniões responderão
`409 meetings_module_disabled`. A coleção de Arquivos continuará disponível,
mas excluirá Arquivos alcançáveis exclusivamente por um módulo desativado.
Arquivos pertencentes diretamente ao Projeto continuarão visíveis; quando
houver mais de um caminho de acesso, bastará que ao menos um deles pertença a
um módulo ativo.

A coleção de Arquivos abrangerá Arquivos pertencentes diretamente ao Projeto,
às suas Tarefas e às Reuniões diretamente vinculadas, além dos Arquivos
explicitamente compartilhados com essas Reuniões. Cada Arquivo aparecerá uma
única vez e indicará seu Proprietário do arquivo. A resposta não explicará o
caminho de acesso por propriedade ou compartilhamento, pois essa distinção é
interna e não agrega valor suficiente ao consumidor. Arquivos de Projeto pai,
subprojetos ou Reuniões sem vínculo direto não serão incluídos apenas por essa
relação.

A coleção de Arquivos aceitará `search`, `owner_type[]`, `mime_type[]`,
`uploaded_from`, `uploaded_to` e `per_page`. A busca abrangerá o nome de
exibição; o tipo de proprietário aceitará `project`, `task` e `meeting`; o
tipo MIME exigirá correspondência exata; e as datas serão limites inclusivos
em ISO 8601. Sem filtros, os Arquivos serão ordenados por data de upload e
identificador decrescentes. A paginação usará 20 itens por padrão e aceitará
de 1 a 100.

Cada item da coleção retornará UUID, nome de exibição, extensão, tipo MIME,
tamanho em bytes, data de upload, resumo do Proprietário do arquivo e URL de
download. O resumo será adaptado ao tipo: Projeto com identificador, slug e
nome; Tarefa ou Reunião com identificador e título. Nome original, caminho,
disco, usuário responsável pelo upload e miniatura não serão expostos.

Links externos não integrarão essa API nesta etapa. Embora a interface web os
apresente junto dos Arquivos e lhes aplique relações semelhantes, eles
continuam sendo outro tipo de recurso e não serão misturados em `/files` nem
receberão endpoints próprios agora.

Todos os endpoints adotarão o mesmo contrato de falhas: `401` para credencial
ausente ou inválida; `403` para Chave válida sem a ability exigida; `404` para
slug, identificador ou UUID inexistente ou fora do escopo do Projeto, sem
confirmar a existência em outro Projeto; `409` para módulo necessário
desativado, com código técnico estável; e `422` para paginação, filtro,
enumeração ou intervalo inválido, no formato de validação do Laravel.

O grupo `/api` limitará as chamadas a 60 requisições por minuto por
endereço IP. Cada listagem, detalhe ou download contará como uma requisição e
o excesso responderá `429`. Nesta versão não haverá cota adicional por Chave
de API, controle por volume de bytes nem limite específico para Arquivos.
