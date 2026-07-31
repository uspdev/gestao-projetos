const { csrfHeaders } = require("./http");

let activeMentionSelector = null;

function closeMentionSelector() {
    if (activeMentionSelector) {
        activeMentionSelector.element.remove();
        activeMentionSelector = null;
    }
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

function insertMention(editor, range, target) {
    const type = mentionType(target);
    const label = mentionMarkdownLabel(target);
    editor.codemirror.replaceRange(
        `@[${label}](mention:${type}:${target.id})`,
        range.from,
        range.to,
    );
    editor.codemirror.focus();
    closeMentionSelector();
}

function renderMentionSelector(editor, range, targets) {
    closeMentionSelector();

    if (targets.length === 0) {
        return;
    }

    const selector = document.createElement("div");
    selector.id = "mention-selector";
    selector.className = "list-group position-absolute shadow";
    selector.style.zIndex = "1060";
    selector.style.minWidth = "16rem";
    const position = editor.codemirror.cursorCoords(range.to, "page");
    selector.style.left = `${position.left}px`;
    selector.style.top = `${position.bottom + 4}px`;

    const filters = document.createElement("div");
    filters.className = "btn-group btn-group-sm w-100 mb-2";
    const filterOptions = [
        ["all", "Todos"],
        ["user", "Pessoas"],
        ["project", "Projetos"],
        ["task", "Tarefas"],
    ];
    const results = document.createElement("div");
    results.className = "list-group";

    const renderResults = (filter) => {
        results.replaceChildren();
        const filteredTargets = filter === "all"
            ? targets
            : targets.filter((target) => mentionType(target) === filter);
        const groups = [];

        filteredTargets.forEach((target) => {
            const type = mentionType(target);
            let group = groups.find((candidate) => candidate.type === type);

            if (!group) {
                group = { type, targets: [] };
                groups.push(group);
            }

            group.targets.push(target);
        });

        activeMentionSelector.activeTargets = filteredTargets;
        activeMentionSelector.activeIndex = 0;

        groups.forEach((group, groupIndex) => {
            const heading = document.createElement("div");
            heading.className = `small text-muted font-weight-bold${
                groupIndex > 0 ? " mt-2" : ""
            }`;
            heading.textContent = mentionTypeLabel(group.targets[0]);
            results.appendChild(heading);

            group.targets.forEach((target) => {
                const option = document.createElement("button");
                option.type = "button";
                option.className = `list-group-item list-group-item-action${
                    filteredTargets.indexOf(target) === 0 ? " active" : ""
                }`;
                option.dataset.mentionTargetType = mentionType(target);
                option.dataset.mentionTargetId = target.id;
                if (mentionType(target) === "user") {
                    option.dataset.mentionUserId = target.id;
                }
                if (mentionType(target) === "project") {
                    option.dataset.mentionProjectId = target.id;
                }
                if (mentionType(target) === "task") {
                    option.dataset.mentionTaskId = target.id;
                }
                option.textContent = `${mentionTypeLabel(target)}: ${mentionLabel(target)}`;
                option.addEventListener("click", () => {
                    insertMention(editor, range, target);
                });
                results.appendChild(option);
            });
        });
    };

    filterOptions.forEach(([value, label], index) => {
        const button = document.createElement("button");
        button.type = "button";
        button.className = `btn btn-outline-secondary${index === 0 ? " active" : ""}`;
        button.dataset.mentionFilter = value;
        button.textContent = label;
        button.addEventListener("click", () => {
            filters.querySelectorAll("[data-mention-filter]").forEach((filterButton) => {
                filterButton.classList.toggle("active", filterButton === button);
            });
            renderResults(value);
        });
        filters.appendChild(button);
    });

    document.body.appendChild(selector);
    selector.append(filters, results);
    activeMentionSelector = {
        element: selector,
        editor,
        range,
        targets,
        activeTargets: targets,
        activeIndex: 0,
        results,
    };
    renderResults("all");
}

function selectActiveMention() {
    if (!activeMentionSelector || activeMentionSelector.activeTargets.length === 0) {
        return false;
    }

    insertMention(
        activeMentionSelector.editor,
        activeMentionSelector.range,
        activeMentionSelector.activeTargets[activeMentionSelector.activeIndex],
    );

    return true;
}

function moveActiveMention(step) {
    if (!activeMentionSelector) {
        return false;
    }

    const { activeTargets, element } = activeMentionSelector;

    if (activeTargets.length === 0) {
        return false;
    }

    activeMentionSelector.activeIndex = (
        activeMentionSelector.activeIndex + step + activeTargets.length
    ) % activeTargets.length;
    element.querySelectorAll("[data-mention-target-type]").forEach((option) => {
        const optionIndex = activeTargets.findIndex((target) =>
            mentionType(target) === option.dataset.mentionTargetType
            && String(target.id) === String(option.dataset.mentionTargetId)
        );
        option.classList.toggle(
            "active",
            optionIndex === activeMentionSelector.activeIndex,
        );
    });

    return true;
}

function loadMentionSelector(textarea, editor, range = mentionRange(editor)) {
    const searchUrl = textarea.dataset.mentionSearchUrl;

    if (!searchUrl || !range) {
        closeMentionSelector();
        return;
    }

    const url = new URL(searchUrl, window.location.origin);
    url.searchParams.set("term", range.term);
    const requestId = Symbol("mention-request");
    textarea.mentionRequestId = requestId;

    window.fetch(url.toString(), {
        credentials: "same-origin",
        headers: csrfHeaders(),
    })
        .then((response) => {
            if (!response.ok) {
                throw new Error(`Falha ao consultar Menções: HTTP ${response.status}`);
            }

            return response.json();
        })
        .then((payload) => {
            if (textarea.mentionRequestId !== requestId) {
                return;
            }

            renderMentionSelector(
                editor,
                range,
                Array.isArray(payload.results) ? payload.results : [],
            );
        })
        .catch(() => closeMentionSelector());
}

function handleMentionKeydown(event, editor) {
    if (!activeMentionSelector || activeMentionSelector.editor !== editor) {
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
