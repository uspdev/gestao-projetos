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
                  <details>
                    <summary class="text-primary" role="button">ver detalhes</summary>
                    <dl class="row small mb-0 mt-2">
                      @foreach ($item['changes'] as $change)
                        <dt class="col-sm-4 mb-1">{{ $change['field'] }}</dt>
                        <dd class="col-sm-8 mb-1">
                          @if ($change['old'] !== null)
                            <span class="text-muted">{{ $change['old'] }}</span>
                            <i class="fas fa-arrow-right mx-1" aria-hidden="true"></i>
                          @endif
                          {{ $change['new'] ?? '—' }}
                        </dd>
                      @endforeach
                    </dl>
                  </details>
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
@endsection
