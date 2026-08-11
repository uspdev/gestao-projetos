import { csrfHeaders } from "./http.js";
import { highlightCodeBlocks } from "./markdown-renderer.js";

const DEBOUNCE_MS = 500;

/**
 * Controla a pré-visualização Markdown gerada pelo servidor.
 *
 * A classe utiliza debounce, cancelamento de requisições obsoletas e número de
 * revisão para impedir que respostas antigas sobrescrevam novas.
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

    isVisible() {
        return this.editor.isPreviewActive();
    }

    element() {
        const preview = this.editor.codemirror.getWrapperElement().lastChild;

        return preview.classList.contains("editor-preview-full")
            ? preview
            : null;
    }

    /**
     * O EasyMDE exige retorno síncrono, por isso a atualização remota ocorre
     * separadamente por meio de `schedule`.
     */
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

        this.timer = window.setTimeout(
            () => this.request(revision),
            DEBOUNCE_MS,
        );
    }

    async request(revision) {
        if (!this.isVisible()) {
            return;
        }

        const abortController = new AbortController();
        this.abortController = abortController;
        const headers = {
            ...csrfHeaders(),
            Accept: "text/html",
            "Content-Type": "application/json",
        };

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
            highlightCodeBlocks(preview);
        } catch (error) {
            // A última resposta válida permanece visível em falhas de rede ou validação.
        } finally {
            if (this.abortController === abortController) {
                this.abortController = null;
            }
        }
    }
}

export { OfficialPreview };
