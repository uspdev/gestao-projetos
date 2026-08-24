/**
 * Retorna os cabeçalhos comuns das requisições AJAX.
 *
 * O token CSRF é incluído somente quando a meta tag existe na página.
 */
function csrfHeaders() {
    // querySelector busca a meta tag uma vez; sem ela, a requisição segue sem CSRF.
    const csrfToken = document.querySelector('meta[name="csrf-token"]');
    const headers = {
        Accept: "application/json",
        "X-Requested-With": "XMLHttpRequest",
    };

    if (csrfToken) {
        headers["X-CSRF-TOKEN"] = csrfToken.getAttribute("content");
    }

    return headers;
}

export { csrfHeaders };
