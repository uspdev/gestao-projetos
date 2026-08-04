const { csrfHeaders } = require("./http");

let activeMentionSelector = null;
let activeMentionRequest = null;

function closeMentionSelector() {
    if (activeMentionRequest?.controller) {
        activeMentionRequest.controller.abort();
    }

    if (activeMentionRequest?.textarea) {
        activeMentionRequest.textarea.mentionRequest = null;
    }

    if (activeMentionSelector?.input) {
        activeMentionSelector.input.removeAttribute("role");
        activeMentionSelector.input.removeAttribute("aria-autocomplete");
        activeMentionSelector.input.removeAttribute("aria-controls");
        activeMentionSelector.input.removeAttribute("aria-expanded");
        activeMentionSelector.input.removeAttribute("aria-activedescendant");
    }

    if (activeMentionSelector) {
        activeMentionSelector.element.remove();
        activeMentionSelector = null;
    }

    activeMentionRequest = null;
}

function mentionRange(editor) {
    const codeMirror = editor.codemirror;
    const cursor = codeMirror.getCursor();
    const beforeCursor = codeMirror.getRange({ line: cursor.line, ch: 0 }, cursor);
    const match = beforeCursor.match(/(^|\s)@([^\s@]*)$/u);

    if (!match) {
        return null;
    }

    return {
        from: { line: cursor.line, ch: match.index + match[1].length },
        to: cursor,
        term: match[2],
    };
}

function mentionType(target) {
    return target.type || "user";
}

function mentionTypeLabel(target) {
    const labels = {
        user: "Pessoa",
        project: "Projeto",
        task: "Tarefa",
        meeting: "Reunião",
        file: "Arquivo",
    };

    return target.type_label || labels[mentionType(target)] || "Destino";
}

function mentionLabel(target) {
    return target.name || target.title || target.label || "";
}

function mentionMarkdownLabel(target) {
    return mentionLabel(target)
        .replace(/\\/g, "\\\\")
        .replace(/\[/g, "\\[")
        .replace(/\]/g, "\\]");
}

function sameMentionRange(left, right) {
    return Boolean(left && right)
        && left.term === right.term
        && left.from.line === right.from.line
        && left.from.ch === right.from.ch
        && left.to.line === right.to.line
        && left.to.ch === right.to.ch;
}

function insertMention(editor, range, target) {
    if (!sameMentionRange(mentionRange(editor), range)) {
        closeMentionSelector();
        return false;
    }

    const type = mentionType(target);
    const label = mentionMarkdownLabel(target);
    editor.codemirror.replaceRange(
        `@[${label}](mention:${type}:${target.id})`,
        range.from,
        range.to,
    );
    editor.codemirror.focus();
    closeMentionSelector();

    return true;
}

function updateActiveMentionOption(index) {
    if (!activeMentionSelector) {
        return;
    }

    if (activeMentionSelector.activeTargets.length === 0) {
        activeMentionSelector.activeIndex = -1;
        activeMentionSelector.input.removeAttribute("aria-activedescendant");
        return;
    }

    activeMentionSelector.activeIndex = index;
    const options = [...activeMentionSelector.element.querySelectorAll(
        "[data-mention-target-type]",
    )];

    options.forEach((option) => {
        const optionIndex = Number(option.dataset.mentionIndex);
        const isActive = optionIndex === index;
        option.classList.toggle("active", isActive);
        option.setAttribute("aria-selected", isActive ? "true" : "false");
    });

    const activeOption = options.find(
        (option) => Number(option.dataset.mentionIndex) === index,
    );

    if (activeOption) {
        activeMentionSelector.input.setAttribute(
            "aria-activedescendant",
            activeOption.id,
        );
    } else {
        activeMentionSelector.input.removeAttribute("aria-activedescendant");
    }
}

function renderMentionSelector(editor, range, targets) {
    closeMentionSelector();

    if (targets.length === 0) {
        return;
    }

    const selector = document.createElement("div");
    selector.id = "mention-selector";
    selector.className = "list-group position-absolute shadow";
    selector.setAttribute("role", "listbox");
    selector.setAttribute("aria-label", "Destinos mencionáveis");
    selector.tabIndex = -1;
    selector.style.zIndex = "1060";
    selector.style.minWidth = "16rem";
    const input = editor.codemirror.getInputField();
    input.setAttribute("role", "combobox");
    input.setAttribute("aria-autocomplete", "list");
    input.setAttribute("aria-controls", selector.id);
    input.setAttribute("aria-expanded", "true");
    const position = editor.codemirror.cursorCoords(range.to, "page");
    selector.style.left = `${position.left}px`;
    selector.style.top = `${position.bottom + 4}px`;

    const filters = document.createElement("div");
    filters.className = "btn-group btn-group-sm w-100 mb-2";
    filters.setAttribute("role", "group");
    filters.setAttribute("aria-label", "Filtrar destinos mencionáveis");
    const filterOptions = [
        ["user", "Usuários"],
        ["project", "Projetos"],
        ["task", "Tarefas"],
        ["meeting", "Reuniões"],
        ["file", "Arquivos"],
    ];
    const results = document.createElement("div");
    results.className = "list-group";

    const renderResults = (filter) => {
        results.replaceChildren();
        const filteredTargets = filter === "all"
            ? targets
            : targets.filter((target) => mentionType(target) === filter);

        activeMentionSelector.activeTargets = filteredTargets;
        activeMentionSelector.activeIndex = 0;
        filters.querySelectorAll("[data-mention-filter]").forEach((button) => {
            const isActive = button.dataset.mentionFilter === filter;
            button.classList.toggle("active", isActive);
            button.setAttribute("aria-pressed", isActive ? "true" : "false");
        });

        filteredTargets.forEach((target, targetIndex) => {
            const option = document.createElement("button");
            option.type = "button";
            option.className = "list-group-item list-group-item-action";
            option.id = `mention-option-${targetIndex}`;
            option.setAttribute("role", "option");
            option.setAttribute("aria-selected", "false");
            option.dataset.mentionTargetType = mentionType(target);
            option.dataset.mentionTargetId = target.id;
            option.dataset.mentionIndex = targetIndex;
            if (mentionType(target) === "user") {
                option.dataset.mentionUserId = target.id;
            }
            if (mentionType(target) === "project") {
                option.dataset.mentionProjectId = target.id;
            }
            if (mentionType(target) === "task") {
                option.dataset.mentionTaskId = target.id;
            }
            if (mentionType(target) === "file") {
                option.dataset.mentionFileId = target.id;
            }
            const label = mentionLabel(target);
            const accessibleName = `${mentionTypeLabel(target)}: ${label}`;
            option.textContent = label;
            option.setAttribute("aria-label", accessibleName);
            option.addEventListener("click", () => {
                insertMention(editor, range, target);
            });
            option.addEventListener("mouseenter", () => {
                updateActiveMentionOption(targetIndex);
            });
            option.addEventListener("focus", () => {
                updateActiveMentionOption(targetIndex);
            });
            results.appendChild(option);
        });

        updateActiveMentionOption(0);
    };

    filterOptions.forEach(([value, label], index) => {
        const button = document.createElement("button");
        button.type = "button";
        button.className = `btn btn-outline-secondary${index === 0 ? " active" : ""}`;
        button.setAttribute("aria-pressed", index === 0 ? "true" : "false");
        button.dataset.mentionFilter = value;
        button.textContent = label;
        button.addEventListener("click", () => {
            renderResults(value);
        });
        filters.appendChild(button);
    });

    document.body.appendChild(selector);
    selector.append(filters, results);
    selector.addEventListener("keydown", (event) => {
        if (event.key !== "Escape" && event.target?.closest?.("[data-mention-filter]")) {
            return;
        }

        handleMentionKeydown(event, editor);
    });
    activeMentionSelector = {
        element: selector,
        editor,
        input,
        range,
        targets,
        activeTargets: targets,
        activeIndex: 0,
        results,
    };
    renderResults("user");
}

function selectActiveMention() {
    if (!activeMentionSelector || activeMentionSelector.activeTargets.length === 0) {
        return false;
    }

    return insertMention(
        activeMentionSelector.editor,
        activeMentionSelector.range,
        activeMentionSelector.activeTargets[activeMentionSelector.activeIndex],
    );
}

function moveActiveMention(step) {
    if (!activeMentionSelector) {
        return false;
    }

    const { activeTargets } = activeMentionSelector;

    if (activeTargets.length === 0) {
        return false;
    }

    const nextIndex = (
        activeMentionSelector.activeIndex + step + activeTargets.length
    ) % activeTargets.length;
    updateActiveMentionOption(nextIndex);

    return true;
}

function loadMentionSelector(textarea, editor, range = mentionRange(editor)) {
    const searchUrl = textarea.dataset.mentionSearchUrl;

    if (!searchUrl || !range) {
        if (textarea.mentionRequest) {
            textarea.mentionRequest = null;
        }
        closeMentionSelector();
        return;
    }

    closeMentionSelector();
    const url = new URL(searchUrl, window.location.origin);
    url.searchParams.set("term", range.term);
    const request = {
        textarea,
        editor,
        range,
        controller: typeof AbortController === "function"
            ? new AbortController()
            : null,
    };
    textarea.mentionRequest = request;
    activeMentionRequest = request;

    window.fetch(url.toString(), {
        credentials: "same-origin",
        headers: csrfHeaders(),
        ...(request.controller ? { signal: request.controller.signal } : {}),
    })
        .then((response) => {
            if (!response.ok) {
                throw new Error(`Falha ao consultar Menções: HTTP ${response.status}`);
            }

            return response.json();
        })
        .then((payload) => {
            if (textarea.mentionRequest !== request
                || activeMentionRequest !== request
                || !sameMentionRange(mentionRange(editor), range)) {
                return;
            }

            renderMentionSelector(
                editor,
                range,
                Array.isArray(payload.results) ? payload.results : [],
            );
        })
        .catch(() => {
            if (textarea.mentionRequest === request
                && activeMentionRequest === request) {
                closeMentionSelector();
            }
        });
}

function handleMentionKeydown(event, editor) {
    if (!activeMentionSelector) {
        if (event.key === "Escape" && activeMentionRequest?.editor === editor) {
            event.preventDefault();
            closeMentionSelector();
        }

        return;
    }

    if (activeMentionSelector.editor !== editor) {
        return;
    }

    if (event.key === "ArrowDown" || event.key === "ArrowUp") {
        event.preventDefault();
        moveActiveMention(event.key === "ArrowDown" ? 1 : -1);
    } else if (event.key === "Enter" || event.key === "Tab") {
        event.preventDefault();
        selectActiveMention();
    } else if (event.key === "Escape") {
        closeMentionSelector();
    }
}

module.exports = {
    handleMentionKeydown,
    loadMentionSelector,
    mentionRange,
};
