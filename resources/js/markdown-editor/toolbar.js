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
 */
function extensionAction(eventName) {
    return function action(editor) {
        // bubbles permite que markdown-editor.js trate o evento no documento.
        editor.element.dispatchEvent(
            new CustomEvent(eventName, {
                bubbles: true,
                detail: { editor },
            }),
        );
    };
}

const mentionButton = {
    name: "mention",
    action: extensionAction("markdown-editor:mention"),
    className: "fa fa-at",
    title: "Mencionar",
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

/**
 * Monta a toolbar de acordo com o perfil do editor.
 *
 * O perfil "compact" remove ações menos usadas para ocupar menos espaço.
 */
function toolbarFor(profile, previewAction) {
    const emphasis = ["bold", "italic"];
    const lists = ["quote", "unordered-list", "ordered-list"];
    const linkAndCode = ["link", inlineCodeButton, codeBlockButton];
    const extensions = [mentionButton];

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

export { toolbarFor };
