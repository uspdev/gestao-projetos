import {
    getEditorEntry,
    initializeMarkdownEditors,
    refreshEditors,
} from "./markdown-editor/editor-registry.js";
import {
    loadMentionSelector,
    mentionRange,
} from "./markdown-editor/mentions.js";
import {
    openFileReferenceSelector,
} from "./markdown-editor/file-reference-selector.js";
import { highlightMarkdown } from "./markdown-editor/markdown-renderer.js";
import { toolbarFor } from "./markdown-editor/toolbar.js";

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

export {
    initializeMarkdownEditors,
    toolbarFor,
};
