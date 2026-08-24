import { csrfHeaders } from "./http.js";

let activeMentionSelector = null;
let activeMentionRequest = null;
const mentionFilterStates = new WeakMap();
const MENTION_DISPLAY_LABEL_LIMIT = 50;

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

function mentionDisplayLabel(label) {
    if (label.length <= MENTION_DISPLAY_LABEL_LIMIT) {
        return label;
    }

    return `${label.slice(0, MENTION_DISPLAY_LABEL_LIMIT - 3)}...`;
}

function mentionScopeLabel(type, scope) {
    const labels = {
        project: {
            contextual: "Projetos relacionados",
            global: "Outros projetos acessíveis",
        },
        task: {
            contextual: "Tarefas relacionadas",
            global: "Outras tarefas acessíveis",
        },
        meeting: {
            contextual: "Reuniões relacionadas",
            global: "Outras reuniões acessíveis",
        },
    };

    return labels[type]?.[scope] || null;
}

function mentionSupportsExpandedSearch(type) {
    return type === "project" || type === "task" || type === "meeting";
}

function mentionGlobalSearchHint(type) {
    const labels = {
        project: "Digite o nome para buscar outros projetos",
        task: "Digite o nome para buscar outras tarefas",
        meeting: "Digite o nome para buscar outras reuniões",
    };

    return labels[type] || "Digite o nome para buscar outros destinos";
}

function mentionTargetIsCompleted(target) {
    return ["task", "meeting"].includes(mentionType(target))
        && target.completed === true;
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

function sameMentionAnchor(left, right) {
    return Boolean(left && right)
        && left.line === right.line
        && left.ch === right.ch;
}

function rememberMentionFilter(textarea, editor, range, filter) {
    mentionFilterStates.set(textarea, {
        editor,
        from: { ...range.from },
        filter,
    });
}

function mentionFilterFor(textarea, editor, range) {
    if (activeMentionSelector?.textarea === textarea
        && activeMentionSelector.editor === editor
        && sameMentionAnchor(activeMentionSelector.range.from, range.from)) {
        return activeMentionSelector.activeFilter;
    }

    const state = mentionFilterStates.get(textarea);

    if (state?.editor === editor && sameMentionAnchor(state.from, range.from)) {
        return state.filter;
    }

    mentionFilterStates.delete(textarea);

    return "user";
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
    if (activeMentionSelector?.textarea) {
        mentionFilterStates.delete(activeMentionSelector.textarea);
    }
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
    // querySelectorAll lista as opções visíveis para atualizar o estado ARIA.
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

function renderMentionSelector(
    textarea,
    editor,
    range,
    targets,
    initialFilter = "user",
) {
    closeMentionSelector();

    if (targets.length === 0 && range.term.trim() !== "") {
        return;
    }

    const selector = document.createElement("div");
    selector.id = "mention-selector";
    selector.className = "mention-selector list-group position-absolute shadow";
    selector.setAttribute("role", "listbox");
    selector.setAttribute("aria-label", "Destinos mencionáveis");
    selector.tabIndex = -1;
    selector.style.zIndex = "1060";
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
    results.className = "list-group mention-selector-results";

    const renderResults = (filter) => {
        results.replaceChildren();
        // Tarefas e reuniões ficam separadas por status para facilitar a escolha.
        const filteredTargets = filter === "all"
            ? targets
            : targets.filter((target) => mentionType(target) === filter);
        const isStatusFilter = filter === "task" || filter === "meeting";
        const activeStatusTargets = isStatusFilter
            ? filteredTargets.filter((target) => !mentionTargetIsCompleted(target))
            : [];
        const completedStatusTargets = isStatusFilter
            ? filteredTargets.filter((target) => mentionTargetIsCompleted(target))
            : [];
        const visibleTargets = isStatusFilter
            ? activeStatusTargets.concat(completedStatusTargets)
            : filteredTargets;

        activeMentionSelector.activeFilter = filter;
        rememberMentionFilter(textarea, editor, range, filter);
        activeMentionSelector.activeTargets = visibleTargets;
        activeMentionSelector.activeIndex = 0;
        // Atualiza visualmente o filtro ativo sem recriar os botões.
        filters.querySelectorAll("[data-mention-filter]").forEach((button) => {
            const isActive = button.dataset.mentionFilter === filter;
            button.classList.toggle("active", isActive);
            button.setAttribute("aria-pressed", isActive ? "true" : "false");
        });

        let previousScope = null;
        let targetIndex = 0;

        const appendScopeLabel = (target) => {
            if (mentionSupportsExpandedSearch(filter) && range.term.trim() !== "") {
                const scope = target.scope === "global" ? "global" : "contextual";
                const label = mentionScopeLabel(filter, scope);

                if (previousScope !== scope && label) {
                    const scopeLabel = document.createElement("div");
                    scopeLabel.className = "mention-selector-scope-label small text-muted font-weight-bold";
                    scopeLabel.dataset.mentionScopeLabel = scope;
                    scopeLabel.dataset.mentionScope = scope;
                    scopeLabel.setAttribute("role", "presentation");
                    scopeLabel.textContent = label;
                    results.appendChild(scopeLabel);
                    previousScope = scope;
                }
            }
        };

        const appendOption = (target) => {
            const option = document.createElement("button");
            const optionIndex = targetIndex;
            option.type = "button";
            option.className = "list-group-item list-group-item-action";
            option.id = `mention-option-${targetIndex}`;
            option.setAttribute("role", "option");
            option.setAttribute("aria-selected", "false");
            option.dataset.mentionTargetType = mentionType(target);
            option.dataset.mentionTargetId = target.id;
            option.dataset.mentionIndex = optionIndex;
            if (mentionType(target) === "user") {
                option.dataset.mentionUserId = target.id;
            }
            if (mentionType(target) === "project") {
                option.dataset.mentionProjectId = target.id;
            }
            if (mentionType(target) === "task") {
                const taskCompleted = mentionTargetIsCompleted(target);
                option.dataset.mentionTaskId = target.id;
                option.dataset.mentionTaskCompleted = taskCompleted
                    ? "true"
                    : "false";
                option.classList.add(
                    "mention-status-option",
                    "mention-task-option",
                    taskCompleted
                        ? "mention-task-option--completed"
                        : "mention-task-option--active",
                );
            }
            if (mentionType(target) === "meeting") {
                const meetingCompleted = mentionTargetIsCompleted(target);
                option.dataset.mentionMeetingId = target.id;
                option.dataset.mentionMeetingCompleted = meetingCompleted
                    ? "true"
                    : "false";
                option.classList.add(
                    "mention-status-option",
                    "mention-meeting-option",
                    meetingCompleted
                        ? "mention-status-option--completed"
                        : "mention-status-option--active",
                );
            }
            if (mentionType(target) === "file") {
                option.dataset.mentionFileId = target.id;
            }
            const label = mentionLabel(target);
            const isStatusTarget = ["task", "meeting"].includes(mentionType(target));
            const targetCompleted = mentionTargetIsCompleted(target);
            const accessibleName = isStatusTarget
                ? `${mentionTypeLabel(target)} ${targetCompleted ? "concluída" : "em andamento"}: ${label}`
                : `${mentionTypeLabel(target)}: ${label}`;
            if (isStatusTarget) {
                const optionLabel = document.createElement("span");
                const statusIndicator = document.createElement("span");

                optionLabel.className = "mention-option-label";
                optionLabel.textContent = mentionDisplayLabel(label);
                statusIndicator.className = "mention-status-indicator";
                if (mentionType(target) === "task") {
                    statusIndicator.classList.add("mention-task-status-indicator");
                    statusIndicator.dataset.mentionTaskStatus = targetCompleted
                        ? "completed"
                        : "active";
                }
                if (mentionType(target) === "meeting") {
                    statusIndicator.classList.add("mention-meeting-status-indicator");
                    statusIndicator.dataset.mentionMeetingStatus = targetCompleted
                        ? "completed"
                        : "active";
                }
                statusIndicator.dataset.mentionStatus = targetCompleted
                    ? "completed"
                    : "active";
                statusIndicator.setAttribute("aria-hidden", "true");
                statusIndicator.textContent = targetCompleted ? "✓" : "●";
                option.append(optionLabel, statusIndicator);
            } else {
                option.textContent = mentionDisplayLabel(label);
            }
            option.setAttribute("aria-label", accessibleName);
            // Os eventos mantêm clique, mouse e foco sincronizados com o teclado.
            option.addEventListener("click", () => {
                insertMention(editor, range, target);
            });
            option.addEventListener("mouseenter", () => {
                updateActiveMentionOption(optionIndex);
            });
            option.addEventListener("focus", () => {
                updateActiveMentionOption(optionIndex);
            });
            results.appendChild(option);
            targetIndex += 1;
        };

        const appendStatusSection = (key, label, sectionTargets) => {
            if (sectionTargets.length === 0) {
                return;
            }

            const sectionLabel = document.createElement("div");
            sectionLabel.className = "mention-status-section-label small text-muted font-weight-bold";
            sectionLabel.dataset.mentionStatusSection = key;
            if (filter === "task") {
                sectionLabel.dataset.mentionTaskSection = key;
            }
            if (filter === "meeting") {
                sectionLabel.dataset.mentionMeetingSection = key;
            }
            sectionLabel.setAttribute("role", "presentation");
            sectionLabel.textContent = label;
            results.appendChild(sectionLabel);

            previousScope = null;
            sectionTargets.forEach((target) => {
                appendScopeLabel(target);
                appendOption(target);
            });
        };

        if (isStatusFilter) {
            appendStatusSection("active", "Em andamento", activeStatusTargets);
            appendStatusSection("completed", "Concluídas", completedStatusTargets);
        } else {
            filteredTargets.forEach((target) => {
                appendScopeLabel(target);
                appendOption(target);
            });
        }

        if (mentionSupportsExpandedSearch(filter) && range.term.trim() === "") {
            const hint = document.createElement("div");
            hint.className = "mention-selector-global-search-hint small text-muted";
            hint.dataset.mentionGlobalSearchHint = "true";
            hint.setAttribute("role", "note");
            hint.textContent = mentionGlobalSearchHint(filter);
            results.appendChild(hint);
        }

        updateActiveMentionOption(0);
    };

    filterOptions.forEach(([value, label], index) => {
        const button = document.createElement("button");
        button.type = "button";
        button.className = `btn btn-outline-secondary${index === 0 ? " active" : ""}`;
        button.setAttribute("aria-pressed", index === 0 ? "true" : "false");
        button.dataset.mentionFilter = value;
        button.textContent = label;
        // O clique troca o filtro e renderiza novamente apenas a lista de resultados.
        button.addEventListener("click", () => {
            renderResults(value);
        });
        filters.appendChild(button);
    });

    document.body.appendChild(selector);
    selector.append(filters, results);
    // Centraliza Escape/Enter/setas para permitir navegação sem mouse.
    selector.addEventListener("keydown", (event) => {
        if (event.key !== "Escape" && event.target?.closest?.("[data-mention-filter]")) {
            return;
        }

        handleMentionKeydown(event, editor);
    });
    activeMentionSelector = {
        element: selector,
        editor,
        textarea,
        input,
        range,
        targets,
        activeFilter: initialFilter,
        activeTargets: targets,
        activeIndex: 0,
        results,
    };
    renderResults(initialFilter);
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
        mentionFilterStates.delete(textarea);
        closeMentionSelector();
        return;
    }

    const activeFilter = mentionFilterFor(textarea, editor, range);
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

    // A resposta só é aplicada se ainda corresponder ao texto e à requisição atuais.
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
                textarea,
                editor,
                range,
                Array.isArray(payload.results) ? payload.results : [],
                activeFilter,
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

export {
    handleMentionKeydown,
    loadMentionSelector,
    mentionRange,
};
