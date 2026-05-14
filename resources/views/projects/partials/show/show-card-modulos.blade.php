<div class="card mb-4">
  <div class="card-header d-flex align-items-center">
    <div class="d-flex align-items-center flex-wrap">
      <h6 class="m-0 text-muted mr-2">
        <i class="fas fa-puzzle-piece mr-1"></i> Módulos
      </h6>
    </div>
  </div>
  <ul class="list-group list-group-flush">
    @forelse (($resolvedModules ?? []) as $module)
      <li class="list-group-item d-flex align-items-center justify-content-between">
        <span>{{ $module['name'] }}</span>
        <span class="badge {{ $module['enabled'] ? 'badge-success' : 'badge-secondary' }}">
          {{ $module['enabled'] ? 'Ativo' : 'Inativo' }}
        </span>
      </li>
    @empty
      <li class="list-group-item text-muted">Nenhum módulo configurado.</li>
    @endforelse
  </ul>
</div>
