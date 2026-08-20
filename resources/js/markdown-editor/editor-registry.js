import {
    handleMentionKeydown,
    loadMentionSelector,
} from "./mentions.js";
import { OfficialPreview } from "./official-preview.js";
import { toolbarFor } from "./toolbar.js";

// WeakMap evita manter referências de textareas removidos do DOM.
const editors = new WeakMap();
const COMPACT_EDITOR_MIN_HEIGHT = "60px";

/**
 * Impede que arquivos sejam inseridos diretamente por arrastar ou colar.
 * Referências devem passar pelo fluxo controlado do seletor de Arquivos.
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

function initializeEditor(textarea) {
    if (editors.has(textarea)) {
        return editors.get(textarea).editor;
    }

    const EasyMDE = window.EasyMDE;

    if (typeof EasyMDE !== "function") {
        return null;
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
    // Sincroniza o textarea original e agenda a pré-visualização a cada alteração.
    editor.codemirror.on("change", () => {
        // Mantenha o textarea sincronizado para o validador de formulários.
        editor.codemirror.save();
        textarea.dispatchEvent(new Event("input", { bubbles: true }));
        preview.schedule();
    });
    editor.codemirror.on("inputRead", () =>
        loadMentionSelector(textarea, editor),
    );
    // Encaminha teclas do seletor de menções para navegação e seleção.
    editor.codemirror.getWrapperElement().addEventListener("keydown", (event) => {
        handleMentionKeydown(event, editor);
    });
    preventFileInsertion(editor);

    editors.set(textarea, { editor, preview });

    return editor;
}

function initializeMarkdownEditors(root = document) {
    // querySelectorAll encontra os editores dentro da raiz; a própria raiz também
    // é aceita quando o MutationObserver entrega um textarea diretamente.
    const textareas =
        root.matches && root.matches("[data-markdown-editor]")
            ? [root]
            : Array.from(root.querySelectorAll("[data-markdown-editor]"));

    return textareas.map(initializeEditor);
}

function refreshEditors(root) {
    // Atualiza somente editores presentes na área que mudou de tamanho/visibilidade.
    const textareas = root.querySelectorAll("[data-markdown-editor]");

    textareas.forEach((textarea) => {
        const entry = editors.get(textarea);

        if (entry) {
            entry.editor.codemirror.refresh();
        }
    });
}

function getEditorEntry(textarea) {
    return editors.get(textarea);
}

export {
    getEditorEntry,
    initializeMarkdownEditors,
    refreshEditors,
};
