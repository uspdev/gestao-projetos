const {
    handleMentionKeydown,
    loadMentionSelector,
} = require("./mentions");
const { OfficialPreview } = require("./official-preview");
const { toolbarFor } = require("./toolbar");

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
    editor.codemirror.on("change", () => {
        // Mantenha o textarea sincronizado para o validador de formulários.
        editor.codemirror.save();
        textarea.dispatchEvent(new Event("input", { bubbles: true }));
        preview.schedule();
    });
    editor.codemirror.on("inputRead", () =>
        loadMentionSelector(textarea, editor),
    );
    editor.codemirror.getWrapperElement().addEventListener("keydown", (event) => {
        handleMentionKeydown(event, editor);
    });
    preventFileInsertion(editor);

    editors.set(textarea, { editor, preview });

    return editor;
}

function initializeMarkdownEditors(root = document) {
    const textareas =
        root.matches && root.matches("[data-markdown-editor]")
            ? [root]
            : Array.from(root.querySelectorAll("[data-markdown-editor]"));

    return textareas.map(initializeEditor);
}

function refreshEditors(root) {
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

module.exports = {
    getEditorEntry,
    initializeMarkdownEditors,
    refreshEditors,
};
