const {
    getEditorEntry,
    initializeMarkdownEditors,
    refreshEditors,
} = require("./markdown-editor/editor-registry");
const {
    loadMentionSelector,
    mentionRange,
} = require("./markdown-editor/mentions");
const {
    openFileReferenceSelector,
} = require("./markdown-editor/file-reference-selector");
const { highlightMarkdown } = require("./markdown-editor/markdown-renderer");
const { toolbarFor } = require("./markdown-editor/toolbar");

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
    document.addEventListener("markdown-editor:mention", (event) => {
        const editor = event.detail.editor;
        const range = mentionRange(editor);

        if (range) {
            loadMentionSelector(event.target, editor, range);
            return;
        }

        editor.codemirror.replaceSelection("@");
        editor.codemirror.focus();
        loadMentionSelector(event.target, editor);
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

// API mínima para inicialização manual e consulta de instâncias existentes.
window.MarkdownEditors = {
    initialize: initializeMarkdownEditors,
    get: getEditorEntry,
};

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", startMarkdownEditors);
} else {
    startMarkdownEditors();
}

// Mantém a API CommonJS já consumida pelo projeto.
module.exports = {
    initializeMarkdownEditors,
    toolbarFor,
};
