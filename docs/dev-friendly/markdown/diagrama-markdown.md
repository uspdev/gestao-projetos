# Fluxo de Markdown

O Markdown atravessa as camadas abaixo desde a edição até a exibição. O texto
original permanece como fonte de verdade; o HTML é uma representação derivada.

```mermaid
flowchart TD
    subgraph presentation["Apresentação no servidor — Blade"]
        textarea["Componente Blade gera um textarea com as configurações do editor"]
        content["Componente Blade solicita a exibição do conteúdo salvo"]
    end

    subgraph browser["Navegador — JavaScript"]
        editor["EasyMDE transforma o textarea em um editor de Markdown"]
        author["Pessoa edita texto, menções e referências de arquivos"]
        preview["Pré-visualização envia o Markdown ao servidor sem salvá-lo"]
        display["HTML seguro é exibido; highlight.js realça blocos de código"]
    end

    subgraph external["Bibliotecas externas — jsDelivr"]
        easyMdeCdn["EasyMDE 2.20.0"]
        highlightCdn["Bundle comum do highlight.js 11.11.1"]
    end

    subgraph application["Aplicação — Laravel"]
        validation["FormRequest autoriza e valida o limite do campo"]
        controller["Controller salva o conteúdo em uma transação"]
        previewController["Endpoint de pré-visualização recebe o Markdown"]
        renderer["MarkdownRenderer converte CommonMark e GFM em HTML seguro"]
        mentions["MentionManager valida as menções e atualiza o índice derivado"]
    end

    subgraph persistence["Persistência — banco de dados e arquivos"]
        database["Campo da entidade guarda o Markdown original"]
        mentionIndex["Tabela de menções guarda o índice derivado"]
        files["Arquivos e permissões são mantidos separadamente"]
    end

    textarea --> editor --> author
    easyMdeCdn -. "SRI aprovado" .-> editor
    highlightCdn -. "SRI aprovado" .-> display
    author -. "pré-visualizar" .-> preview --> previewController --> renderer --> display
    author -- "enviar formulário" --> validation --> controller
    controller --> database
    controller --> mentions --> mentionIndex
    database -- "consultar página" --> content --> renderer
    renderer --> display
    files -. "seletor insere somente a referência" .-> author
```

## Responsabilidade de cada camada

- **Blade:** disponibiliza o `textarea` e solicita a renderização do conteúdo salvo.
- **Navegador:** carrega EasyMDE e `highlight.js` globalmente pelo jsDelivr; o
  EasyMDE melhora a edição, a pré-visualização envia Markdown e o
  `highlight.js` atua apenas no realce de código já convertido em HTML. Se o
  CDN falhar, o `textarea` e o conteúdo sem realce permanecem utilizáveis.
- **Laravel:** valida a entrada, grava o Markdown original, atualiza o índice de
  menções e usa o `MarkdownRenderer` na pré-visualização e na exibição final.
- **Persistência:** o campo da entidade guarda o Markdown; menções são um índice
  derivado; arquivos e permissões ficam sob responsabilidade do módulo de arquivos.

O `MarkdownRenderer` escapa HTML escrito pelo usuário, aceita links relativos e
links `http` ou `https`, abre links em nova aba e impede protocolos inseguros.
Imagens em Markdown são convertidas em links seguros, não incorporadas à página.
