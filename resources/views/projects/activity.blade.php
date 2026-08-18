@extends('projects.layouts.project')

@section('project-content')
  <div class="card border-0">
    <form method="GET" action="{{ route('projects.activity', $project) }}" class="border rounded p-3 mb-4">
      <div class="row align-items-end">
        <div class="col-12 col-lg-6 mb-3 mb-lg-0">
          <label for="activity-search" class="mb-1">Buscar</label>
          <input
            type="search"
            id="activity-search"
            name="search"
            value="{{ $filters['search'] ?? '' }}"
            class="form-control"
            placeholder="Pesquise em qualquer campo do histórico">
        </div>
        <div class="col-12 col-md-5 col-lg-2 mb-3 mb-lg-0">
          <label for="activity-from" class="mb-1">De</label>
          <input
            type="date"
            id="activity-from"
            name="from"
            value="{{ $filters['from'] ?? '' }}"
            class="form-control">
        </div>
        <div class="col-12 col-md-5 col-lg-2 mb-3 mb-lg-0">
          <label for="activity-until" class="mb-1">Até</label>
          <input
            type="date"
            id="activity-until"
            name="until"
            value="{{ $filters['until'] ?? '' }}"
            class="form-control">
        </div>
        <div class="col-12 col-md-2 col-lg-2 d-flex gap-2">
          <button type="submit" class="btn btn-primary flex-grow-1">
            <i class="fas fa-filter mr-1" aria-hidden="true"></i> Filtrar
          </button>
          @if (($filters['search'] ?? '') !== '' || filled($filters['from'] ?? null) || filled($filters['until'] ?? null))
            <a href="{{ route('projects.activity', $project) }}" class="btn btn-outline-secondary" title="Limpar filtros"
              aria-label="Limpar filtros">
              <i class="fas fa-times" aria-hidden="true"></i>
            </a>
          @endif
        </div>
      </div>
      <small class="form-text text-muted">
        A busca considera a ação, usuário, item afetado e os valores registrados antes e depois da alteração.
      </small>
    </form>

    <div class="card-header bg-white px-0 pt-0 d-flex justify-content-between align-items-center">
      <div>
        <h1 class="h5 mb-1">Histórico de alterações</h1>
        <p class="text-muted mb-0">Acompanhe as mudanças realizadas neste projeto e em seus itens.</p>
      </div>
      <span class="badge badge-light border">{{ $activities->total() }} registros</span>
    </div>

    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead>
          <tr>
            <th scope="col">Quando</th>
            <th scope="col">Quem</th>
            <th scope="col">Ação</th>
            <th scope="col">Item</th>
            <th scope="col">Alterações</th>
          </tr>
        </thead>
        <tbody>
          @forelse ($activities as $item)
            <tr>
              <td class="text-nowrap">
                <x-local-datetime :date="$item['record']->created_at" />
              </td>
              <td>{{ $item['actor'] }}</td>
              <td class="text-nowrap text-capitalize">{{ $item['action'] }}</td>
              <td>{{ $item['subject'] }}</td>
              <td>
                @if (count($item['changes']) > 0)
                  <button
                    type="button"
                    class="btn btn-sm btn-outline-primary"
                    data-toggle="modal"
                    data-target="#activity-details-modal"
                    data-activity-changes="{{ json_encode($item['changes'], JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT | JSON_HEX_TAG) }}">
                    Ver detalhes
                  </button>
                @else
                  <span class="text-muted">Sem detalhes</span>
                @endif
              </td>
            </tr>
          @empty
            <tr>
              <td colspan="5" class="text-center text-muted py-5">
                <i class="fas fa-history fa-2x mb-3 d-block" aria-hidden="true"></i>
                @if (($filters['search'] ?? '') !== '' || filled($filters['from'] ?? null) || filled($filters['until'] ?? null))
                  Nenhuma alteração encontrada com os filtros informados.
                @else
                  Nenhuma alteração registrada para este projeto.
                @endif
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>

    @if ($activities->hasPages())
      <div class="mt-3">
        {{ $activities->links() }}
      </div>
    @endif
  </div>

  <div class="modal fade" id="activity-details-modal" tabindex="-1" role="dialog"
    aria-labelledby="activity-details-modal-label" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h2 class="modal-title h5" id="activity-details-modal-label">Detalhes da alteração</h2>
          <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <dl class="row small mb-0" data-activity-details></dl>
        </div>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
  <script>
    (function() {
      const modal = document.getElementById('activity-details-modal');
      const details = modal?.querySelector('[data-activity-details]');

      if (!modal || !details || !window.jQuery) return;

      window.jQuery(modal).on('show.bs.modal', function(event) {
        const changes = JSON.parse(event.relatedTarget?.dataset.activityChanges || '[]');

        details.replaceChildren();

        changes.forEach(function(change) {
          const field = document.createElement('dt');
          field.className = 'col-sm-4 mb-2';
          field.textContent = change.field;

          const value = document.createElement('dd');
          value.className = 'col-sm-8 mb-2';

          if (change.old !== null) {
            const old = document.createElement('span');
            old.className = 'text-muted';
            old.textContent = change.old;
            value.appendChild(old);

            const arrow = document.createElement('i');
            arrow.className = 'fas fa-arrow-right mx-1';
            arrow.setAttribute('aria-hidden', 'true');
            value.appendChild(arrow);
          }

          value.append(document.createTextNode(change.new ?? '—'));
          details.append(field, value);
        });
      });
    })();
  </script>
@endpush
