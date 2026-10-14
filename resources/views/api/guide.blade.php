<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="light">
    <title>API de leitura · Gestão de Projetos</title>
    <style>
        :root {
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            color: #16324f;
            background: #f7fbff;
            --primary: #004b87;
            --text: #16324f;
            --muted: #4a6072;
            --border: #d5e3ee;
        }
        * { box-sizing: border-box; }
        body { margin: 0; line-height: 1.6; background: radial-gradient(circle at top left, #e9f5ec 0%, transparent 45%), linear-gradient(135deg, #f7fbff 0%, #fff 100%); }
        code { font-family: ui-monospace, SFMono-Regular, Consolas, "Liberation Mono", monospace; font-size: .96em; }
        .shell { width: min(1400px, calc(100% - 64px)); margin: 0 auto; padding: 56px 0 48px; }
        .hero { margin-bottom: 46px; padding: 48px 32px; border: 1px solid var(--border); border-radius: 18px; background: #fff; box-shadow: 0 16px 48px rgba(22, 50, 79, .08); text-align: center; }
        .eyebrow { display: inline-block; margin: 0 0 18px; padding: 6px 16px; border: 1px solid #c3dbf1; border-radius: 999px; background: #e6f0fa; color: var(--primary); font-size: .85rem; font-weight: 700; letter-spacing: .04em; text-transform: uppercase; }
        h1 { margin: 0; color: var(--text); font-size: clamp(2rem, 4vw, 3rem); font-weight: 800; line-height: 1.2; }
        .lead { max-width: 750px; margin: 16px auto 0; color: var(--muted); font-size: 1.15rem; }
        .not-found { max-width: 720px; margin: 0 auto 24px; padding: 13px 17px; border: 1px solid #eeab54; border-radius: 10px; background: #fff5e6; color: #634312; text-align: left; }
        .not-found strong { display: block; }
        h2 { margin: 0 0 24px; color: var(--text); font-size: 1.8rem; font-weight: 700; text-align: center; }
        .group { margin: 30px 0 12px; color: var(--primary); font-size: 1rem; font-weight: 800; }
        .endpoints { display: grid; gap: 18px; }
        .endpoint { overflow: hidden; border: 1px solid var(--border); border-radius: 16px; background: #fff; box-shadow: 0 8px 28px rgba(22, 50, 79, .07); }
        .endpoint-summary { display: block; position: relative; padding: 23px 26px; list-style: none; cursor: pointer; }
        .endpoint-summary::-webkit-details-marker { display: none; }
        .endpoint-summary::marker { content: ''; }
        .endpoint-summary::after { content: ''; position: absolute; top: 34px; right: 28px; width: 10px; height: 10px; border: solid #54718a; border-width: 0 2px 2px 0; transform: rotate(45deg); transition: transform .2s ease; }
        .endpoint[open] .endpoint-summary::after { transform: rotate(225deg); }
        .endpoint-summary:focus-visible { outline: 3px solid var(--primary); outline-offset: -3px; border-radius: 16px; }
        .endpoint-summary:hover { background: #fbfdff; }
        .endpoint-heading { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; padding-right: 34px; }
        .method { padding: 6px 10px; border-radius: 6px; background: #d6f1e9; color: #075d44; font: 800 .88rem ui-monospace, monospace; }
        .endpoint-title { color: var(--text); font-size: 1.4rem; font-weight: 700; }
        .endpoint .path { display: block; margin-top: 17px; padding: 12px 15px; border-left: 4px solid #087e91; border-radius: 5px; background: #eff8fe; overflow-wrap: anywhere; color: #174a65; font-size: 1.1rem; font-weight: 700; }
        .endpoint-body { padding: 0 26px 26px; }
        .endpoint-body::before { content: ''; display: block; border-top: 1px solid var(--border); margin-bottom: 22px; }
        .endpoint .meta { display: inline-block; padding: 5px 9px; border-radius: 6px; background: #f0f4f8; color: #385675; font-size: .93rem; font-weight: 700; }
        .endpoint dl { display: grid; grid-template-columns: 115px minmax(0, 1fr); gap: 16px 18px; margin: 22px 0 0; font-size: 1.04rem; }
        .endpoint dt { color: var(--text); font-weight: 800; }
        .endpoint dd { margin: 0; color: #31485f; overflow-wrap: anywhere; }
        .endpoint dd code { padding: 2px 4px; border-radius: 4px; background: #edf5f9; color: #075d70; }
        @media (max-width: 720px) { .shell { width: min(1400px, calc(100% - 32px)); padding-top: 24px; } .hero { padding: 32px 20px; margin-bottom: 34px; } .endpoint-summary { padding: 20px; } .endpoint-summary::after { right: 22px; top: 31px; } .endpoint-body { padding: 0 20px 20px; } .endpoint dl { grid-template-columns: 1fr; gap: 4px; } .endpoint dd { margin-bottom: 13px; } }
        @media print { body { background: #fff; } .hero { box-shadow: none; } .endpoint { break-inside: avoid; box-shadow: none; } }
    </style>
</head>
<body>
    <div class="shell">
        <header class="hero">
            @if ($notFound ?? false)
                <div class="not-found" role="status">
                    <strong>404 · Rota não encontrada</strong>
                    O endereço solicitado não corresponde a um endpoint. Use a lista abaixo para escolher uma rota disponível.
                </div>
            @endif
            <p class="eyebrow">Gestão de Projetos · API pública de leitura</p>
            <h1>Endpoints da API de leitura</h1>
            <p class="lead">Consulte Projetos, Reuniões, Tarefas e Arquivos.</p>
        </header>

        <main>
            <section aria-labelledby="endpoints-title">
                <h2 id="endpoints-title">Endpoints disponíveis</h2>
                <div class="endpoints">
                    <div class="group" id="projeto">Projeto</div>
                    <details class="endpoint" id="get-project">
                        <summary class="endpoint-summary">
                            <span class="endpoint-heading"><span class="method">GET</span><span class="endpoint-title">Detalhar Projeto</span></span>
                            <code class="path">/api/projects/{project}</code>
                        </summary>
                        <div class="endpoint-body">
                            <div class="meta">Ability exigida: <code>projects.read</code></div>
                            <dl>
                                <dt>Retorno</dt><dd>JSON em <code>data</code> com <code>id</code>, <code>slug</code>, <code>name</code>, <code>description</code>, <code>status</code>, <code>visibility</code>, <code>permission_inheritance</code>, <code>type</code>, <code>phase</code>, <code>parent</code>, <code>tags</code>, <code>modules_enabled</code>, <code>members</code>, <code>comments</code>, <code>files</code>, <code>links</code>, <code>incoming_mentions</code>, <code>agenda_meetings</code>, <code>subprojects</code>, <code>web_url</code> e timestamps.</dd>
                                <dt>Parâmetro</dt><dd><code>{project}</code> é o slug do Projeto.</dd>
                                <dt>Acesso</dt><dd>Envie a Chave de API vinculada ao Projeto em <code>Authorization: Bearer</code>. Se o slug mudar, atualize as URLs: as anteriores deixam de funcionar.</dd>
                            </dl>
                        </div>
                    </details>

                    <div class="group" id="reunioes">Reuniões</div>
                    <details class="endpoint" id="get-meetings">
                        <summary class="endpoint-summary">
                            <span class="endpoint-heading"><span class="method">GET</span><span class="endpoint-title">Listar Reuniões</span></span>
                            <code class="path">/api/projects/{project}/meetings</code>
                        </summary>
                        <div class="endpoint-body">
                            <div class="meta">Ability exigida: <code>meetings.read</code></div>
                            <dl>
                                <dt>Retorno</dt><dd>JSON em <code>data</code> com as Reuniões diretamente vinculadas ao Projeto: <code>id</code>, <code>title</code>, <code>status</code>, <code>scheduled_at</code>, <code>location</code>, <code>projects</code>, timestamps e <code>web_url</code>.</dd>
                                <dt>Filtros</dt><dd><code>status[]</code>, <code>scheduled_from</code>, <code>scheduled_to</code> (ISO 8601 com fuso) e <code>search</code> (título ou local).</dd>
                            </dl>
                        </div>
                    </details>
                    <details class="endpoint" id="get-meeting">
                        <summary class="endpoint-summary">
                            <span class="endpoint-heading"><span class="method">GET</span><span class="endpoint-title">Detalhar Reunião</span></span>
                            <code class="path">/api/projects/{project}/meetings/{meeting}</code>
                        </summary>
                        <div class="endpoint-body">
                            <div class="meta">Ability exigida: <code>meetings.read</code></div>
                            <dl>
                                <dt>Retorno</dt><dd>JSON em <code>data</code> com os campos da listagem, além de <code>notes</code> (Anotações prévias), <code>ata</code>, <code>transcription</code>, <code>agenda</code> ordenada (ID e posição dos itens), <code>comments</code> (IDs e autores), <code>files</code> e <code>links</code> próprios e compartilhados e <code>incoming_mentions</code>.</dd>
                                <dt>Parâmetro</dt><dd><code>{meeting}</code> é o ID numérico obtido na listagem.</dd>
                            </dl>
                        </div>
                    </details>

                    <div class="group" id="tarefas">Tarefas</div>
                    <details class="endpoint" id="get-tasks">
                        <summary class="endpoint-summary">
                            <span class="endpoint-heading"><span class="method">GET</span><span class="endpoint-title">Listar Tarefas</span></span>
                            <code class="path">/api/projects/{project}/tasks</code>
                        </summary>
                        <div class="endpoint-body">
                            <div class="meta">Ability exigida: <code>tasks.read</code></div>
                            <dl>
                                <dt>Retorno</dt><dd>JSON em <code>data</code> com as Tarefas: <code>id</code>, <code>title</code>, <code>description</code> integral, <code>status</code>, <code>priority</code>, datas de início, vencimento e conclusão, <code>assignees</code>, <code>tags</code>, timestamps e <code>web_url</code>.</dd>
                                <dt>Filtros</dt><dd><code>status[]</code>, <code>priority[]</code>, <code>due_from</code>, <code>due_to</code> (YYYY-MM-DD), <code>tag[]</code> (slug) e <code>search</code> (título ou descrição).</dd>
                            </dl>
                        </div>
                    </details>
                    <details class="endpoint" id="get-task">
                        <summary class="endpoint-summary">
                            <span class="endpoint-heading"><span class="method">GET</span><span class="endpoint-title">Detalhar Tarefa</span></span>
                            <code class="path">/api/projects/{project}/tasks/{task}</code>
                        </summary>
                        <div class="endpoint-body">
                            <div class="meta">Ability exigida: <code>tasks.read</code></div>
                            <dl>
                                <dt>Retorno</dt><dd>JSON em <code>data</code> com os campos da listagem, mais <code>project</code>, responsáveis com ID, e-mail e papel, <code>comments</code> (com IDs e autores), <code>files</code>, <code>links</code> e <code>incoming_mentions</code>.</dd>
                                <dt>Parâmetro</dt><dd><code>{task}</code> é o ID numérico obtido na listagem.</dd>
                            </dl>
                        </div>
                    </details>

                    <div class="group" id="arquivos">Arquivos</div>
                    <details class="endpoint" id="get-files">
                        <summary class="endpoint-summary">
                            <span class="endpoint-heading"><span class="method">GET</span><span class="endpoint-title">Listar Arquivos</span></span>
                            <code class="path">/api/projects/{project}/files</code>
                        </summary>
                        <div class="endpoint-body">
                            <div class="meta">Ability exigida: <code>files.read</code></div>
                            <dl>
                                <dt>Retorno</dt><dd>JSON em <code>data</code> com <code>uuid</code>, <code>name</code>, <code>extension</code>, <code>mime_type</code>, <code>size</code>, <code>uploaded_at</code>, <code>owner</code> e <code>download_url</code> de cada Arquivo.</dd>
                                <dt>Filtros</dt><dd><code>search</code> (nome exibido), <code>owner_type[]</code> (<code>project</code>, <code>task</code> ou <code>meeting</code>), <code>mime_type[]</code>, <code>uploaded_from</code> e <code>uploaded_to</code> (ISO 8601 com fuso).</dd>
                            </dl>
                        </div>
                    </details>
                    <details class="endpoint" id="get-file">
                        <summary class="endpoint-summary">
                            <span class="endpoint-heading"><span class="method">GET</span><span class="endpoint-title">Baixar Arquivo</span></span>
                            <code class="path">/api/projects/{project}/files/{uuid}</code>
                        </summary>
                        <div class="endpoint-body">
                            <div class="meta">Ability exigida: <code>files.read</code></div>
                            <dl>
                                <dt>Retorno</dt><dd>Conteúdo binário original, com nome seguro em <code>Content-Disposition: attachment</code> e o tipo em <code>Content-Type</code>. Não retorna JSON.</dd>
                                <dt>Parâmetro</dt><dd><code>{uuid}</code> é o UUID obtido na listagem (ou use <code>download_url</code>).</dd>
                            </dl>
                        </div>
                    </details>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
