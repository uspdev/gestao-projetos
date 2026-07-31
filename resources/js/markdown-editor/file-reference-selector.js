const { fileDownloadUrl } = require("./file-reference-navigation");
const { csrfHeaders } = require("./http");

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
    editor.codemirror.replaceSelection(
        `[${file.name}](${fileDownloadUrl(file.uuid)})`,
    );
    editor.codemirror.focus();
}

function createSelectorButton(file, label, action) {
    const button = document.createElement("button");
    button.type = "button";
    button.className =
        "btn btn-outline-primary btn-sm btn-block text-left mb-2";
    button.textContent = label || file.name;
    action(button);

    return button;
}

function selectorGroups(groups, fallbackResults) {
    const validGroups = Array.isArray(groups)
        ? groups.filter(
              (group) =>
                  Array.isArray(group.results) &&
                  group.results.length > 0,
          )
        : [];

    return validGroups.length > 0
        ? validGroups
        : fallbackResults.length > 0
          ? [{ results: fallbackResults }]
          : [];
}

function appendSelectorGroupHeading(body, group, tagName, className) {
    if (!group.label) {
        return;
    }

    const heading = document.createElement(tagName);
    heading.className = className;
    heading.textContent = group.label;
    body.appendChild(heading);
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
            const resultGroups = selectorGroups(
                payload.result_groups,
                results,
            );

            if (results.length === 0) {
                const empty = document.createElement("p");
                empty.className = "text-muted mb-0";
                empty.textContent = "Nenhum Arquivo disponível neste contexto.";
                body.appendChild(empty);
            } else {
                resultGroups.forEach((group, index) => {
                    appendSelectorGroupHeading(
                        body,
                        group,
                        "h3",
                        `h6 text-muted${index > 0 ? " mt-3" : ""}`,
                    );

                    group.results.forEach((file) => {
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
                });
            }

            const shareable = Array.isArray(payload.shareable_results)
                ? payload.shareable_results
                : [];
            const shareableGroups = selectorGroups(
                payload.shareable_groups,
                shareable,
            );

            if (textarea.dataset.fileShareUrl && shareableGroups.length > 0) {
                const heading = document.createElement("h3");
                heading.className = "h6 mt-4";
                heading.textContent =
                    "Arquivos que podem ser compartilhados com a reunião";
                body.appendChild(heading);

                shareableGroups.forEach((group) => {
                    appendSelectorGroupHeading(
                        body,
                        group,
                        "h4",
                        "h6 text-muted mt-3",
                    );

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

module.exports = { openFileReferenceSelector };
