import {
    configureFileReference,
    currentPageAnchorUrl,
    referencedFileUuid,
} from "./file-reference-navigation.js";

/**
 * Aplica highlight.js aos blocos de código já renderizados no DOM.
 */
function highlightCodeBlocks(root) {
    if (!window.hljs || typeof window.hljs.highlightElement !== "function") {
        return;
    }

    root.querySelectorAll("pre code").forEach((block) => {
        window.hljs.highlightElement(block);
    });
}

function highlightMarkdown(root = document) {
    const markdownContents = root.matches && root.matches(".markdown-content")
        ? [root]
        : root.querySelectorAll(".markdown-content");

    markdownContents.forEach((content) => {
        highlightCodeBlocks(content);

        content.querySelectorAll("a[href]").forEach((link) => {
            const href = link.getAttribute("href");
            const fileUuid = referencedFileUuid(href);

            if (fileUuid) {
                configureFileReference(link, content, fileUuid);

                return;
            }

            if (href.startsWith("#")) {
                link.setAttribute("href", currentPageAnchorUrl(href));
                link.removeAttribute("target");
                link.removeAttribute("rel");
            }
        });
    });
}

export { highlightCodeBlocks, highlightMarkdown };
