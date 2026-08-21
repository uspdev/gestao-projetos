document.addEventListener("DOMContentLoaded", function () {
    /*
     * Função para obter o elemento alvo a partir do hash da URL.
     * @param {string} hash - O hash da URL.
     * @returns {HTMLElement|null} - O elemento alvo, ou null se não encontrado.
     */
    function targetFromHash(hash) {
        var targetId;

        try {
            targetId = decodeURIComponent((hash || "").replace(/^#/, ""));
        } catch (error) {
            return null;
        }

        return targetId ? document.getElementById(targetId) : null;
    }

    /*
     * Função para revelar todos os elementos colapsados que são ancestrais do elemento alvo.
     * @param {HTMLElement} target - O elemento alvo.
     */
    function revealCollapsedAncestors(target) {
        var collapses = [];
        var ancestor = target.parentElement;

        while (ancestor) {
            if (ancestor.classList.contains("collapse"))
                collapses.push(ancestor);
            ancestor = ancestor.parentElement;
        }

        collapses.reverse().forEach(function (collapse) {
            if (window.jQuery) {
                window.jQuery(collapse).collapse("show");
            } else {
                collapse.classList.add("show");
            }
        });
    }

    /*
     * Função para focar em um link profundo.
     * @param {string} hash - O hash da URL.
     * @returns {void}
     */
    function focusDeepLink(hash) {
        var target = targetFromHash(hash);

        if (
            !target ||
            !target.matches("[data-deep-link-target]") ||
            target.matches("[data-file-card], [data-link-card]")
        )
            return;

        revealCollapsedAncestors(target);

        var expandableId = target.getAttribute("data-deep-link-expand");
        var expandable = expandableId
            ? document.getElementById(expandableId)
            : null;

        if (expandable) {
            if (window.jQuery) {
                window.jQuery(expandable).collapse("show");
            } else {
                expandable.classList.add("show");
            }
        }

        window.requestAnimationFrame(function () {
            target.scrollIntoView({ block: "start" });
            target.focus({ preventScroll: true });
        });
    }

    focusDeepLink(window.location.hash);

    // Adiciona um ouvinte de evento para mudanças no hash da URL, chamando a função focusDeepLink com o novo hash.
    window.addEventListener("hashchange", function () {
        focusDeepLink(window.location.hash);
    });

    // Adiciona um ouvinte de evento para cliques em links, verificando se o link é interno e chamando a função focusDeepLink com o hash do link.
    document.addEventListener("click", function (event) {
        var link = event.target.closest && event.target.closest("a[href]");

        if (!link) return;

        var destination = new URL(link.href, window.location.href);

        if (
            destination.origin === window.location.origin &&
            destination.pathname === window.location.pathname &&
            destination.search === window.location.search
        ) {
            focusDeepLink(destination.hash);
        }
    });
});
