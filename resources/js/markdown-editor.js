const EasyMDE = require('easymde');
const hljs = require('highlight.js');

require('easymde/dist/easymde.min.css');
require('highlight.js/styles/github.css');

const editors = new WeakMap();
const DEBOUNCE_MS = 500;

function wrapSelection(editor, opening, closing = opening) {
    const codeMirror = editor.codemirror;
    const selection = codeMirror.getSelection();

    codeMirror.replaceSelection(`${opening}${selection}${closing}`);
    codeMirror.focus();
}

function insertLinePrefix(editor, prefix) {
    const codeMirror = editor.codemirror;
    const cursor = codeMirror.getCursor('from');

    codeMirror.replaceRange(prefix, { line: cursor.line, ch: 0 });
    codeMirror.focus();
}

function extensionAction(eventName) {
    return function action(editor) {
        editor.element.dispatchEvent(new CustomEvent(eventName, {
            bubbles: true,
            detail: { editor },
        }));
    };
}

const mentionButton = {
    name: 'mention',
    action: extensionAction('markdown-editor:mention'),
    className: 'fa fa-at',
    title: 'Mencionar usuário',
};

const fileReferenceButton = {
    name: 'file-reference',
    action: extensionAction('markdown-editor:file-reference'),
    className: 'fa fa-paperclip',
    title: 'Referenciar Arquivo',
};

const inlineCodeButton = {
    name: 'inline-code',
    action: (editor) => wrapSelection(editor, '`'),
    className: 'fa fa-terminal',
    title: 'Código inline',
};

const codeBlockButton = {
    name: 'code-block',
    action: (editor) => wrapSelection(editor, '```\n', '\n```'),
    className: 'fa fa-code',
    title: 'Bloco de código',
};

const taskListButton = {
    name: 'task-list',
    action: (editor) => insertLinePrefix(editor, '- [ ] '),
    className: 'fa fa-check-square',
    title: 'Lista de tarefas',
};

const helpButton = {
    name: 'markdown-help',
    action: () => window.alert('Use Markdown para formatar títulos, listas, links, tabelas e blocos de código.'),
    className: 'fa fa-question-circle',
    title: 'Ajuda rápida de Markdown',
};

function toolbarFor(profile, previewAction) {
    const emphasis = ['bold', 'italic'];
    const lists = [
        'quote',
        'unordered-list',
        'ordered-list',
    ];
    const linkAndCode = [
        'link',
        inlineCodeButton,
        codeBlockButton,
    ];
    const extensions = [mentionButton, fileReferenceButton];

    if (profile === 'compact') {
        return [...emphasis, ...lists, ...linkAndCode, ...extensions, '|', previewAction];
    }

    return [
        ...emphasis,
        'heading',
        ...lists,
        taskListButton,
        ...linkAndCode,
        'table',
        ...extensions,
        '|',
        previewAction,
        helpButton,
    ];
}

class OfficialPreview {
    constructor(editor, textarea) {
        this.editor = editor;
        this.textarea = textarea;
        this.previewUrl = textarea.dataset.markdownPreviewUrl;
        this.timer = null;
        this.revision = 0;
        this.lastValidHtml = '';
        this.abortController = null;
    }

    isVisible() {
        return this.editor.isPreviewActive();
    }

    element() {
        const preview = this.editor.codemirror.getWrapperElement().lastChild;

        return preview.classList.contains('editor-preview-full') ? preview : null;
    }

    render() {
        this.schedule();

        return this.lastValidHtml;
    }

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

        this.timer = window.setTimeout(() => this.request(revision), DEBOUNCE_MS);
    }

    async request(revision) {
        if (!this.isVisible()) {
            return;
        }

        const abortController = new AbortController();
        this.abortController = abortController;
        const csrfToken = document.querySelector('meta[name="csrf-token"]');
        const headers = {
            Accept: 'text/html',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        };

        if (csrfToken) {
            headers['X-CSRF-TOKEN'] = csrfToken.getAttribute('content');
        }

        try {
            const response = await window.fetch(this.previewUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers,
                body: JSON.stringify({ markdown: this.editor.value() }),
                signal: abortController.signal,
            });

            if (!response.ok) {
                throw new Error(`Falha ao gerar pré-visualização: HTTP ${response.status}`);
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
            preview.querySelectorAll('pre code').forEach((block) => hljs.highlightElement(block));
        } catch (error) {
            // A última resposta válida permanece visível em falhas de rede ou validação.
        } finally {
            if (this.abortController === abortController) {
                this.abortController = null;
            }
        }
    }
}

function preventFileInsertion(editor) {
    const wrapper = editor.codemirror.getWrapperElement();

    wrapper.addEventListener('drop', (event) => {
        if (event.dataTransfer && event.dataTransfer.files.length > 0) {
            event.preventDefault();
        }
    });

    wrapper.addEventListener('paste', (event) => {
        if (event.clipboardData && event.clipboardData.files.length > 0) {
            event.preventDefault();
        }
    });
}

function highlightMarkdown(root = document) {
    root.querySelectorAll('.markdown-content pre code').forEach((block) => {
        hljs.highlightElement(block);
    });
}

function initializeEditor(textarea) {
    if (editors.has(textarea)) {
        return editors.get(textarea).editor;
    }

    let preview;
    const previewAction = {
        name: 'preview',
        action(editor) {
            EasyMDE.togglePreview(editor);
            window.setTimeout(() => preview.schedule(), 10);
        },
        className: 'fa fa-eye no-disable',
        title: 'Pré-visualizar',
    };

    const editor = new EasyMDE({
        element: textarea,
        toolbar: toolbarFor(textarea.dataset.markdownProfile, previewAction),
        autosave: { enabled: false },
        spellChecker: false,
        nativeSpellcheck: true,
        uploadImage: false,
        previewRender: () => preview.render(),
        status: false,
    });

    preview = new OfficialPreview(editor, textarea);
    editor.codemirror.getInputField().setAttribute('spellcheck', 'true');
    editor.codemirror.on('change', () => preview.schedule());
    preventFileInsertion(editor);

    editors.set(textarea, { editor, preview });

    return editor;
}

function initializeMarkdownEditors(root = document) {
    const textareas = root.matches && root.matches('[data-markdown-editor]')
        ? [root]
        : Array.from(root.querySelectorAll('[data-markdown-editor]'));

    return textareas.map(initializeEditor);
}

function refreshEditors(root) {
    const textareas = root.querySelectorAll('[data-markdown-editor]');

    textareas.forEach((textarea) => {
        const entry = editors.get(textarea);

        if (entry) {
            entry.editor.codemirror.refresh();
        }
    });
}

function startMarkdownEditors() {
    initializeMarkdownEditors();
    highlightMarkdown();

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
        window.jQuery(document).on('shown.bs.modal shown.bs.collapse', (event) => {
            initializeMarkdownEditors(event.target);
            refreshEditors(event.target);
        });
    }
}

window.MarkdownEditors = {
    initialize: initializeMarkdownEditors,
    get: (textarea) => editors.get(textarea),
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', startMarkdownEditors);
} else {
    startMarkdownEditors();
}

module.exports = {
    initializeMarkdownEditors,
    toolbarFor,
};
