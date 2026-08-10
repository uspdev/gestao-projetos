# Bibliotecas de front-end por CDN

**Status:** aceito

As bibliotecas externas executadas no navegador serão carregadas globalmente pelo layout principal a partir do jsDelivr, com EasyMDE 2.20.0 e `highlight.js` 11.11.1 fixados nas URLs. Todos os JavaScripts e estilos externos terão SRI e `crossorigin="anonymous"`; uma atualização exigirá a alteração consciente tanto da versão quanto do hash. O jsDelivr será o provedor único para evitar dependências equivalentes distribuídas entre CDNs diferentes.

O código JavaScript e os estilos próprios permanecerão em `resources/` como fontes canônicas e serão publicados em `public/` pelo comando `php artisan assets:publish`. O comando copia as árvores de ativos, empacota os módulos JavaScript locais, resolve as importações CSS e gera o `mix-manifest.json`; ele é executado automaticamente pelo evento `post-autoload-dump` do Composer. Assim, o ambiente de produção não depende de Node.js nem npm. Laravel Mix, Webpack e npm permanecem disponíveis somente como ferramentas opcionais de desenvolvimento e não fornecem bibliotecas Markdown. Os arquivos gerados em `public/` continuarão fora do controle de versão. Essa restrição decorre da política da empresa e preserva a organização de ativos adotada pelo projeto antes da implementação Markdown.

Bibliotecas sem uso observável não serão transferidas para o CDN. O Lodash será removido porque o código apenas o expõe em `window._`, sem nenhum consumidor na aplicação.

Falhas no carregamento do CDN não poderão impedir a edição ou o salvamento dos formulários nem interromper funções locais não relacionadas. Sem o EasyMDE, o campo permanecerá disponível como `textarea`; sem o `highlight.js`, o conteúdo continuará visível sem realce de sintaxe. Não haverá fallback para npm, cópia local ou outro CDN.

Os testes de navegador carregarão os ativos reais do jsDelivr, e acesso à internet será requisito explícito da suíte Dusk. Testes HTTP verificarão as URLs fixas, os hashes SRI, `crossorigin` e a ordem de carregamento; haverá também cobertura da degradação sem os objetos globais. Não serão mantidas cópias locais nem substitutos das bibliotecas para testes.

O `highlight.js` usará o bundle comum oficial para navegador, que cobre as linguagens atualmente documentadas e testadas pelo projeto. Linguagens fora desse conjunto continuarão legíveis sem realce e só serão acrescentadas por ativos CDN separados quando houver necessidade comprovada.

## Ativos aprovados

- EasyMDE CSS: `https://cdn.jsdelivr.net/npm/easymde@2.20.0/dist/easymde.min.css`, com `sha384-3AvV7152TgYAMYdGZPqG9BpmSH2ZW6ewTDL0QV5PyNkl19KMI+yLMdJz183N8A2d`.
- EasyMDE JavaScript: `https://cdn.jsdelivr.net/npm/easymde@2.20.0/dist/easymde.min.js`, com `sha384-YDXeUfPZ4SP6vJpnF+ZMmf4B1bax6yd4Q/aNbkvLidRD843hPG5RE67M0IYT4LOq`.
- Tema GitHub do `highlight.js`: `https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.11.1/build/styles/github.min.css`, com `sha384-eFTL69TLRZTkNfYZOLM+G04821K1qZao/4QLJbet1pP4tcF+fdXq/9CdqAbWRl/L`.
- Bundle comum do `highlight.js`: `https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@11.11.1/build/highlight.min.js`, com `sha384-RH2xi4eIQ/gjtbs9fUXM68sLSi99C7ZWBRX1vDrVv6GQXRibxXLbwO2NGZB74MbU`.
