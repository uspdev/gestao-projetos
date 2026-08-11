import { csrfHeaders } from "./http.js";

function fileDownloadUrl(uuid) {
    const template = window.fileDownloadUrlTemplate;

    return typeof template === "string"
        ? template.replace("__uuid__", encodeURIComponent(uuid))
        : `/files/${uuid}`;
}

function fileNavigationUrl(uuid) {
    const template = window.fileNavigationUrlTemplate;

    return typeof template === "string"
        ? template.replace("__uuid__", encodeURIComponent(uuid))
        : `/files/${uuid}/navigation`;
}

function contextualFileReferenceUrl(url, content) {
    const contextualUrl = new URL(url, window.location.origin);
    const context = content.closest("[data-file-reference-context-type]");

    if (context) {
        contextualUrl.searchParams.set(
            "context_type",
            context.dataset.fileReferenceContextType,
        );
        contextualUrl.searchParams.set(
            "context_id",
            context.dataset.fileReferenceContextId,
        );

        if (context.dataset.fileReferenceContextProjectId) {
            contextualUrl.searchParams.set(
                "context_project_id",
                context.dataset.fileReferenceContextProjectId,
            );
        }
    }

    return `${contextualUrl.pathname}${contextualUrl.search}`;
}

function referencedFileUuid(href) {
    try {
        const url = new URL(href, window.location.origin);

        if (url.origin !== window.location.origin) {
            return null;
        }

        return url.pathname
            .match(/\/files\/([0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12})$/i)
            ?.[1] ?? null;
    } catch {
        return null;
    }
}

function internalUrl(url) {
    const resolved = new URL(url, window.location.origin);

    return resolved.origin === window.location.origin
        ? `${resolved.pathname}${resolved.search}${resolved.hash}`
        : url;
}

function currentPageAnchorUrl(anchor) {
    const url = new URL(window.location.href);
    url.hash = anchor;

    return `${url.pathname}${url.search}${url.hash}`;
}

function configureFileReference(link, content, uuid) {
    if (link.dataset.fileReferenceNavigation) {
        return;
    }

    const anchor = `file-${uuid}`;
    const contextualUrl = contextualFileReferenceUrl(
        fileDownloadUrl(uuid),
        content,
    );

    link.dataset.fileReferenceNavigation = "pending";
    link.setAttribute("href", contextualUrl);

    if (document.getElementById(anchor)) {
        link.setAttribute("href", currentPageAnchorUrl(anchor));
        link.removeAttribute("target");
        link.removeAttribute("rel");
        link.dataset.fileReferenceNavigation = "resolved";

        return;
    }

    window.fetch(
        contextualFileReferenceUrl(fileNavigationUrl(uuid), content),
        {
            method: "GET",
            credentials: "same-origin",
            headers: csrfHeaders(),
        },
    )
        .then((response) => {
            if (!response.ok) {
                throw new Error(
                    `Falha ao resolver referência de Arquivo: HTTP ${response.status}`,
                );
            }

            return response.json();
        })
        .then((destination) => {
            if (typeof destination.url !== "string"
                || typeof destination.opens_new_tab !== "boolean") {
                throw new Error("Destino de referência de Arquivo inválido.");
            }

            link.setAttribute("href", internalUrl(destination.url));

            if (destination.opens_new_tab) {
                link.setAttribute("target", "_blank");
                link.setAttribute("rel", "noopener noreferrer");
            } else {
                link.removeAttribute("target");
                link.removeAttribute("rel");
            }

            link.dataset.fileReferenceNavigation = "resolved";
        })
        .catch(() => {
            link.dataset.fileReferenceNavigation = "failed";
        });
}

export {
    configureFileReference,
    currentPageAnchorUrl,
    fileDownloadUrl,
    referencedFileUuid,
};
