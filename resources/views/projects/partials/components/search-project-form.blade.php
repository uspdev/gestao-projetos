<div class="d-flex align-items-center ml-4 mt-2" style="gap: .5rem;">
  <input id="project-search" type="search" class="form-control form-control-sm" placeholder="Buscar projeto"
    value="{{ $search ?? '' }}" style="width: 250px;" autofocus>
  <button id="project-search-clear" type="button" class="btn btn-outline-secondary btn-sm">Limpar</button>
</div>

@push('scripts')
  <script>
    // Função de busca e filtragem de projetos
    (function() {
      const input = document.getElementById('project-search');
      const clearBtn = document.getElementById('project-search-clear');
      const items = Array.from(document.querySelectorAll('.project-item'));
      const countEl = document.getElementById('projects-count');
      const noResults = document.getElementById('no-results');

      function normalize(s) {
        return (s || '').toLowerCase();
      }

      function applyFilter() {
        const q = normalize(input.value).trim();
        // atualiza a query string para refletir o termo de busca, sem recarregar a página
        try {
          // atualiza a URL com o parâmetro de busca, mantendo outros parâmetros intactos
          const url = new URL(window.location.href);
          if (q === '') {
            // se a busca estiver vazia, remove o parâmetro 'search' da URL
            url.searchParams.delete('search');
          } else {
            url.searchParams.set('search', q);
          }
          window.history.replaceState({}, '', url.toString());
        } catch (e) {
          // se ocorrer algum erro
          console.error('Erro ao atualizar a URL:', e);
        }
        let visible = 0;
        // para cada item, verifica se o termo de busca está presente no conteúdo pesquisável (name, description, tags)
        items.forEach(el => {
          const hay = el.getAttribute('data-searchable') || '';
          const show = q === '' || hay.indexOf(q) !== -1;
          el.style.display = show ? '' : 'none';
          if (show) visible++;
        });
        countEl.textContent = visible;
        noResults.classList.toggle('d-none', visible !== 0);
      }
      // adiciona os event listeners para aplicar o filtro enquanto o usuário digita ou clica no botão de limpar
      input && input.addEventListener('input', applyFilter);
      clearBtn && clearBtn.addEventListener('click', function() {
        input.value = '';
        input.focus();
        applyFilter();
      });
      // aplica o filtro inicial com base no valor presente no campo de busca (útil para manter a busca após recarregar a página)
      if (input) applyFilter();
    })();
  </script>
@endpush
