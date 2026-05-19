@extends('layouts.app')

@section('title', 'Meus Projetos')

@section('content')
  @php
    $allProjects = $projects;
  @endphp

  <div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div class="d-flex align-items-center gap-2">
        <h2 class="mb-0">Meus Projetos</h2>
        @include('projects.partials.components.admin-view-toggle-btn', [
            'allViewLabel' => 'Ver todos',
            'myViewLabel' => 'Ver meus',
            'allViewTitle' => 'Mostrando todos os projetos',
            'myViewTitle' => 'Mostrando apenas meus projetos',
        ])
        @include('projects.partials.components.search-project-form')
      </div>
    </div>

    <section id="projetos-pinnados" class="mb-5">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h4 class="mb-1">Projetos pinados</h4>
        </div>
      </div>

      <div class="row">
        @forelse($pinnedProjects as $project)
          <div class="col-md-4">
            @include('projects.partials.components.preview')
          </div>
        @empty
          <div class="col-12">
            <div class="alert alert-light border mb-0">
              Você ainda não fixou nenhum projeto.
            </div>
          </div>
        @endforelse
      </div>
    </section>

    <section id="todos-projetos">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h4 class="mb-1">Todos os projetos</h4>
          <p class="text-muted mb-0"><span id="projects-count">{{ $allProjects->count() }}</span> projeto(s)
            encontrado(s).</p>
        </div>
      </div>

      <div class="row" id="projects-list">
        @foreach ($allProjects as $project)
          <div class="col-md-4 project-item"
            data-searchable="{{ strtolower($project->name . ' ' . ($project->description ?? '') . ' ' . ($project->tags->pluck('name')->implode(' ') ?? '')) }}">
            @include('projects.partials.components.preview')
          </div>
        @endforeach
      </div>
      <div class="row">
        <div class="col-12">
          <div id="no-results" class="alert alert-info d-none">Nenhum projeto encontrado para sua busca.</div>
        </div>
      </div>
    </section>
  </div>

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
@endsection
