const EasyMDE = require("easymde");
const hljs = require("highlight.js");

require("easymde/dist/easymde.min.css");
require("highlight.js/styles/github.css");

// -----------------------------------------------------------------------------
// Estado e configurações globais
// -----------------------------------------------------------------------------

// WeakMap evita manter referências de textareas removidos do DOM,
// permitindo que o garbage collector libere os editores automaticamente.
const editors = new WeakMap();

const DEBOUNCE_MS = 500;
const COMPACT_EDITOR_MIN_HEIGHT = "60px";

// -----------------------------------------------------------------------------
// Utilitários de edição do conteúdo
// -----------------------------------------------------------------------------

/**
 * Envolve o texto selecionado com marcadores Markdown.
 *
 * Exemplo:
 * wrapSelection(editor, "**") transforma "texto" em "**texto**".
 */
function wrapSelection(editor, opening, closing = opening) {
    const codeMirror = editor.codemirror;
    const selection = codeMirror.getSelection();

    codeMirror.replaceSelection(`${opening}${selection}${closing}`);
    codeMirror.focus();
}

/**
 * Insere um prefixo no início da linha atual.
 *
 * Usado por ações como a criação de itens de lista de tarefas.
 */
function insertLinePrefix(editor, prefix) {
    const codeMirror = editor.codemirror;
    const cursor = codeMirror.getCursor("from");

    codeMirror.replaceRange(prefix, { line: cursor.line, ch: 0 });
    codeMirror.focus();
}

/**
 * Cria uma ação de toolbar que delega seu comportamento por CustomEvent.
 *
 * Isso mantém o editor desacoplado das implementações específicas, como
 * menções e referências de arquivos.
 */
function extensionAction(eventName) {
    return function action(editor) {
        editor.element.dispatchEvent(
            new CustomEvent(eventName, {
                bubbles: true,
                detail: { editor },
            }),
        );
    };
}

// -----------------------------------------------------------------------------
// Botões personalizados da toolbar
// -----------------------------------------------------------------------------

const mentionButton = {
    name: "mention",
    action: extensionAction("markdown-editor:mention"),
    className: "fa fa-at",
    title: "Mencionar usuário",
};

const fileReferenceButton = {
    name: "file-reference",
    action: extensionAction("markdown-editor:file-reference"),
    className: "fa fa-paperclip",
    title: "Referenciar Arquivo",
};

const inlineCodeButton = {
    name: "inline-code",
    action: (editor) => wrapSelection(editor, "`"),
    className: "fa fa-terminal",
    title: "Código inline",
};

const codeBlockButton = {
    name: "code-block",
    action: (editor) => wrapSelection(editor, "```\n", "\n```"),
    className: "fa fa-code",
    title: "Bloco de código",
};

const taskListButton = {
    name: "task-list",
    action: (editor) => insertLinePrefix(editor, "- [ ] "),
    className: "fa fa-check-square",
    title: "Lista de tarefas",
};

// -----------------------------------------------------------------------------
// Configuração da toolbar
// -----------------------------------------------------------------------------

/**
 * Monta a toolbar de acordo com o perfil do editor.
 *
 * O perfil "compact" remove ações menos usadas para ocupar menos espaço.
 */
function toolbarFor(profile, previewAction, supportsFileReferences = true) {
    const emphasis = ["bold", "italic"];
    const lists = ["quote", "unordered-list", "ordered-list"];
    const linkAndCode = ["link", inlineCodeButton, codeBlockButton];
    const extensions = supportsFileReferences
        ? [mentionButton, fileReferenceButton]
        : [mentionButton];

    if (profile === "compact") {
        return [
            ...emphasis,
            ...lists,
            ...linkAndCode,
            ...extensions,
            previewAction,
        ];
    }

    return [
        ...emphasis,
        "heading",
        ...lists,
        taskListButton,
        ...linkAndCode,
        "table",
        ...extensions,
        previewAction,
    ];
}

// -----------------------------------------------------------------------------
// Comunicação HTTP e proteção CSRF
// -----------------------------------------------------------------------------

/**
 * Retorna os cabeçalhos comuns das requisições AJAX.
 *
 * O token CSRF é incluído somente quando a meta tag existe na página.
 */
function csrfHeaders() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    const headers = {
        Accept: "application/json",
        "X-Requested-With": "XMLHttpRequest",
    };

    if (csrfToken) {
        headers["X-CSRF-TOKEN"] = csrfToken.getAttribute("content");
    }

    return headers;
}

// -----------------------------------------------------------------------------
// Seleção e inserção de referências de arquivos
// -----------------------------------------------------------------------------

/**
 * Fecha o seletor usando o modal do Bootstrap, quando disponível,
 * e remove o elemento do DOM como garantia de limpeza.
 */
function closeFileReferenceSelector(selector) {
    if (window.jQuery) {
        window.jQuery(selector).modal("hide");
    }

    selector.remove();
}

/**
 * Insere no editor o link Markdown correspondente ao arquivo selecionado.
 */
function insertFileReference(editor, file) {
    editor.codemirror.replaceSelection(`[${file.name}](/files/${file.uuid})`);
    editor.codemirror.focus();
}

/**
 * Cria um botão reutilizável para um arquivo listado no seletor.
 *
 * O callback `action` configura atributos e eventos específicos do botão.
 */
function createSelectorButton(file, label, action) {
    const button = document.createElement("button");
    button.type = "button";
    button.className =
        "btn btn-outline-primary btn-sm btn-block text-left mb-2";
    button.textContent = label || file.name;
    action(button);

    return button;
}

/**
 * Busca os arquivos disponíveis e monta dinamicamente o modal de seleção.
 *
 * Também permite compartilhar um arquivo com a reunião antes de inserir
 * o Markdown retornado pelo backend.
 */
function openFileReferenceSelector(textarea, editor) {
    const url = textarea.dataset.fileReferenceUrl;

    if (!url) {
        return;
    }

    document.querySelector("#file-reference-selector")?.remove();

    window
        .fetch(url, {
            credentials: "same-origin",
            headers: csrfHeaders(),
        })
        .then((response) => {
            if (!response.ok) {
                throw new Error(
                    `Falha ao consultar Arquivos: HTTP ${response.status}`,
                );
            }

            return response.json();
        })
        .then((payload) => {
            const selector = document.createElement("div");
            selector.id = "file-reference-selector";
            selector.className = "modal fade show";
            selector.tabIndex = -1;
            selector.setAttribute("role", "dialog");
            selector.style.display = "block";
            selector.setAttribute("aria-modal", "true");

            const dialog = document.createElement("div");
            dialog.className = "modal-dialog modal-dialog-scrollable";
            const content = document.createElement("div");
            content.className = "modal-content";
            const header = document.createElement("div");
            header.className = "modal-header";
            const title = document.createElement("h2");
            title.className = "modal-title h5";
            title.textContent = "Referenciar Arquivo";
            const close = document.createElement("button");
            close.type = "button";
            close.className = "close";
            close.setAttribute("aria-label", "Fechar");
            close.textContent = "×";
            close.addEventListener("click", () =>
                closeFileReferenceSelector(selector),
            );
            header.append(title, close);

            const body = document.createElement("div");
            body.className = "modal-body";
            const results = Array.isArray(payload.results)
                ? payload.results
                : [];

            if (results.length === 0) {
                const empty = document.createElement("p");
                empty.className = "text-muted mb-0";
                empty.textContent = "Nenhum Arquivo disponível neste contexto.";
                body.appendChild(empty);
            } else {
                results.forEach((file) => {
                    body.appendChild(
                        createSelectorButton(file, file.name, (button) => {
                            button.dataset.fileReferenceUuid = file.uuid;
                            button.addEventListener("click", () => {
                                insertFileReference(editor, file);
                                closeFileReferenceSelector(selector);
                            });
                        }),
                    );
                });
            }

            const shareable = Array.isArray(payload.shareable_results)
                ? payload.shareable_results
                : [];
            const shareableGroups = Array.isArray(payload.shareable_groups)
                ? payload.shareable_groups.filter(
                      (group) =>
                          Array.isArray(group.results) &&
                          group.results.length > 0,
                  )
                : shareable.length > 0
                  ? [{ results: shareable }]
                  : [];

            if (textarea.dataset.fileShareUrl && shareableGroups.length > 0) {
                const heading = document.createElement("h3");
                heading.className = "h6 mt-4";
                heading.textContent =
                    "Arquivos que podem ser compartilhados com a reunião";
                body.appendChild(heading);

                shareableGroups.forEach((group) => {
                    if (group.label) {
                        const groupHeading = document.createElement("h4");
                        groupHeading.className = "h6 text-muted mt-3";
                        groupHeading.textContent = group.label;
                        body.appendChild(groupHeading);
                    }

                    group.results.forEach((file) => {
                        body.appendChild(
                            createSelectorButton(
                                file,
                                `Compartilhar com a reunião e inserir: ${file.name}`,
                                (button) => {
                                    button.dataset.fileShareUuid = file.uuid;
                                    button.addEventListener("click", () => {
                                        button.disabled = true;
                                        window
                                            .fetch(
                                                textarea.dataset.fileShareUrl,
                                                {
                                                    method: "POST",
                                                    credentials: "same-origin",
                                                    headers: {
                                                        ...csrfHeaders(),
                                                        "Content-Type":
                                                            "application/json",
                                                    },
                                                    body: JSON.stringify({
                                                        media_uuid: file.uuid,
                                                    }),
                                                },
                                            )
                                            .then((response) => {
                                                if (!response.ok) {
                                                    throw new Error(
                                                        `Falha ao compartilhar Arquivo: HTTP ${response.status}`,
                                                    );
                                                }

                                                return response.json();
                                            })
                                            .then((shared) => {
                                                editor.codemirror.replaceSelection(
                                                    shared.markdown,
                                                );
                                                editor.codemirror.focus();
                                                closeFileReferenceSelector(
                                                    selector,
                                                );
                                            })
                                            .catch(() => {
                                                button.disabled = false;
                                            });
                                    });
                                },
                            ),
                        );
                    });
                });
            }

            content.append(header, body);
            dialog.appendChild(content);
            selector.appendChild(dialog);
            document.body.appendChild(selector);
        })
        .catch(() => {
            window.alert("Não foi possível carregar os Arquivos disponíveis.");
        });
}

// -----------------------------------------------------------------------------
// Pré-visualização oficial renderizada pelo backend
// -----------------------------------------------------------------------------

/**
 * Controla a pré-visualização Markdown gerada pelo servidor.
 *
 * A classe utiliza:
 * - debounce para evitar uma requisição a cada tecla;
 * - AbortController para cancelar requisições obsoletas;
 * - número de revisão para impedir que respostas antigas sobrescrevam novas.
 */
class OfficialPreview {
    constructor(editor, textarea) {
        this.editor = editor;
        this.textarea = textarea;
        this.previewUrl = textarea.dataset.markdownPreviewUrl;
        this.timer = null;
        this.revision = 0;
        this.lastValidHtml = "";
        this.abortController = null;
    }

    /**
     * Indica se o modo de pré-visualização está ativo.
     */
    isVisible() {
        return this.editor.isPreviewActive();
    }

    /**
     * Retorna o elemento da pré-visualização em tela cheia.
     */
    element() {
        const preview = this.editor.codemirror.getWrapperElement().lastChild;

        return preview.classList.contains("editor-preview-full")
            ? preview
            : null;
    }

    /**
     * Agenda uma atualização e devolve imediatamente o último HTML válido.
     *
     * O EasyMDE exige retorno síncrono, por isso a atualização remota ocorre
     * separadamente por meio de `schedule`.
     */
    render() {
        this.schedule();

        return this.lastValidHtml;
    }

    /**
     * Reinicia o debounce e cancela uma requisição anterior ainda em andamento.
     */
    schedule() {
        window.clearTimeout(this.timer);
        const revision = ++this.revision;

        if (this.abortController) {
            this.abortController.abort();
            this.abortController = null;
        }

        if (!this.isVisible()) {
            return;
        }

        this.timer = window.setTimeout(
            () => this.request(revision),
            DEBOUNCE_MS,
        );
    }

    /**
     * Solicita ao backend o HTML oficial do Markdown atual.
     *
     * A resposta só é aplicada quando ainda pertence à revisão mais recente
     * e o painel de pré-visualização continua visível.
     */
    async request(revision) {
        if (!this.isVisible()) {
            return;
        }

        const abortController = new AbortController();
        this.abortController = abortController;
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        const headers = {
            Accept: "text/html",
            "Content-Type": "application/json",
            "X-Requested-With": "XMLHttpRequest",
        };

        if (csrfToken) {
            headers["X-CSRF-TOKEN"] = csrfToken.getAttribute("content");
        }

        try {
            const response = await window.fetch(this.previewUrl, {
                method: "POST",
                credentials: "same-origin",
                headers,
                body: JSON.stringify({ markdown: this.editor.value() }),
                signal: abortController.signal,
            });

            if (!response.ok) {
                throw new Error(
                    `Falha ao gerar pré-visualização: HTTP ${response.status}`,
                );
            }

            const html = await response.text();

            if (revision !== this.revision || !this.isVisible()) {
                return;
            }

            this.lastValidHtml = html;
            const preview = this.element();

            if (!preview) {
                return;
            }

            preview.innerHTML = this.lastValidHtml;
            preview
                .querySelectorAll("pre code")
                .forEach((block) => hljs.highlightElement(block));
        } catch (error) {
            // A última resposta válida permanece visível em falhas de rede ou validação.
        } finally {
            if (this.abortController === abortController) {
                this.abortController = null;
            }
        }
    }
}

// -----------------------------------------------------------------------------
// Restrições e realce de sintaxe
// -----------------------------------------------------------------------------

/**
 * Impede que arquivos sejam inseridos diretamente por arrastar ou colar.
 *
 * Referências devem passar pelo fluxo controlado do seletor de arquivos.
 */
function preventFileInsertion(editor) {
    const wrapper = editor.codemirror.getWrapperElement();

    wrapper.addEventListener("drop", (event) => {
        if (event.dataTransfer && event.dataTransfer.files.length > 0) {
            event.preventDefault();
        }
    });

    wrapper.addEventListener("paste", (event) => {
        if (event.clipboardData && event.clipboardData.files.length > 0) {
            event.preventDefault();
        }
    });
}

/**
 * Aplica highlight.js aos blocos de código já renderizados no DOM.
 */
function highlightMarkdown(root = document) {
    root.querySelectorAll(".markdown-content pre code").forEach((block) => {
        hljs.highlightElement(block);
    });
}

// -----------------------------------------------------------------------------
// Inicialização e gerenciamento dos editores
// -----------------------------------------------------------------------------

/**
 * Inicializa um EasyMDE para o textarea informado.
 *
 * Caso o elemento já tenha editor, reutiliza a instância registrada no WeakMap.
 */
function initializeEditor(textarea) {
    if (editors.has(textarea)) {
        return editors.get(textarea).editor;
    }

    let preview;
    const previewAction = {
        name: "preview",
        action(editor) {
            EasyMDE.togglePreview(editor);
            window.setTimeout(() => preview.schedule(), 10);
        },
        className: "fa fa-eye no-disable markdown-preview-button",
        title: "Pré-visualizar",
    };

    const editor = new EasyMDE({
        element: textarea,
        minHeight:
            textarea.dataset.markdownProfile === "compact"
                ? COMPACT_EDITOR_MIN_HEIGHT
                : undefined,
        toolbar: toolbarFor(
            textarea.dataset.markdownProfile,
            previewAction,
            Boolean(textarea.dataset.fileReferenceUrl),
        ),
        autosave: { enabled: false },
        spellChecker: false,
        nativeSpellcheck: true,
        uploadImage: false,
        previewRender: () => preview.render(),
        status: false,
    });

    preview = new OfficialPreview(editor, textarea);
    editor.codemirror.getInputField().setAttribute("spellcheck", "true");
    editor.codemirror.on("change", () => preview.schedule());
    preventFileInsertion(editor);

    editors.set(textarea, { editor, preview });

    return editor;
}

/**
 * Inicializa todos os editores encontrados dentro de um elemento raiz.
 *
 * O próprio elemento raiz também é considerado quando possui
 * `data-markdown-editor`.
 */
function initializeMarkdownEditors(root = document) {
    const textareas =
        root.matches && root.matches("[data-markdown-editor]")
            ? [root]
            : Array.from(root.querySelectorAll("[data-markdown-editor]"));

    return textareas.map(initializeEditor);
}

/**
 * Recalcula o layout de editores exibidos após modal ou collapse ser aberto.
 */
function refreshEditors(root) {
    const textareas = root.querySelectorAll("[data-markdown-editor]");

    textareas.forEach((textarea) => {
        const entry = editors.get(textarea);

        if (entry) {
            entry.editor.codemirror.refresh();
        }
    });
}

// -----------------------------------------------------------------------------
// Eventos globais e conteúdo inserido dinamicamente
// -----------------------------------------------------------------------------

/**
 * Inicializa os editores existentes e registra integrações globais.
 *
 * O MutationObserver garante suporte a formulários adicionados ao DOM depois
 * do carregamento inicial, como conteúdos carregados em modais.
 */
function startMarkdownEditors() {
    initializeMarkdownEditors();
    highlightMarkdown();

    document.addEventListener("markdown-editor:file-reference", (event) => {
        openFileReferenceSelector(event.target, event.detail.editor);
    });

    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === Node.ELEMENT_NODE) {
                    initializeMarkdownEditors(node);
                    highlightMarkdown(node);
                }
            });
        });
    });

    observer.observe(document.body, { childList: true, subtree: true });

    if (window.jQuery) {
        window
            .jQuery(document)
            .on("shown.bs.modal shown.bs.collapse", (event) => {
                initializeMarkdownEditors(event.target);
                refreshEditors(event.target);
            });
    }
}

// -----------------------------------------------------------------------------
// API pública e bootstrap do módulo
// -----------------------------------------------------------------------------

// API mínima para inicialização manual e consulta de instâncias existentes.
window.MarkdownEditors = {
    initialize: initializeMarkdownEditors,
    get: (textarea) => editors.get(textarea),
};

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", startMarkdownEditors);
} else {
    startMarkdownEditors();
}

// Exportações usadas por outros módulos e por testes automatizados.
module.exports = {
    initializeMarkdownEditors,
    toolbarFor,
};
