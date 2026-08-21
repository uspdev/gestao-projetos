@extends('projects.layouts.project')

@section('title', $title . ' | Membros dos subprojetos')

@section('project-content')
  <div id="subproject-members" class="card border mb-4">
    <div class="card-header h6 py-2 d-flex align-items-center">
      <span>
        <i class="fas fa-users-cog mr-1" aria-hidden="true"></i>
        Membros dos subprojetos
      </span>
      <a href="{{ route('projects.settings', $project) }}" class="btn btn-sm btn-outline-secondary ml-2">
        <i class="fas fa-arrow-left mr-1" aria-hidden="true"></i>
        Voltar às configurações
      </a>
    </div>
    <div class="card-body">
      <p class="text-muted mb-3">
        Esta lista apresenta somente os membros vinculados diretamente a cada subprojeto.
      </p>
      <div class="d-flex align-items-center py-0 gap-2 mb-5">
        <input id="subproject-members-search" type="search" class="form-control form-control-sm py-0"
          placeholder="Buscar projeto ou pessoa" aria-label="Buscar projeto ou pessoa"
          style="width: 200px; height: 24px;">
        <button id="subproject-members-search-clear" type="button" class="btn btn-outline-secondary btn-sm py-0">
          Limpar
        </button>
      </div>
      <div id="subproject-members-list">
        @include('projects.partials.show.subproject-members-card')
      </div>

      <p id="subproject-members-no-results" class="text-muted mb-0 d-none" role="status">
        Nenhum projeto ou pessoa encontrado.
      </p>
    </div>
  </div>
@endsection

@push('scripts')
  <script>
    (function() {
      const input = document.getElementById('subproject-members-search');
      const clearButton = document.getElementById('subproject-members-search-clear');
      const list = document.getElementById('subproject-members-list');
      const noResults = document.getElementById('subproject-members-no-results');

      if (!input || !clearButton || !list || !noResults) return;

      const cards = Array.from(list.querySelectorAll('[data-subproject-members-searchable]'));

      function normalize(value) {
        return (value || '')
          .toLowerCase()
          .normalize('NFD')
          .replace(/[\u0300-\u036f]/g, '');
      }

      function applyFilter() {
        const query = normalize(input.value).trim();
        let visibleCards = 0;

        cards.forEach(card => {
          const searchableText = card.getAttribute('data-subproject-members-searchable');
          const matches = query === '' || normalize(searchableText).includes(query);
          card.classList.toggle('d-none', !matches);

          if (matches) visibleCards++;
        });

        noResults.classList.toggle('d-none', cards.length === 0 || visibleCards !== 0);
      }

      clearButton.addEventListener('click', function() {
        input.value = '';
        input.focus();
        applyFilter();
      });

      input.addEventListener('input', applyFilter);
      applyFilter();
    })();
  </script>
@endpush
