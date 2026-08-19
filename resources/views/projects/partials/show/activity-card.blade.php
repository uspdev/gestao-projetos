@can('viewActivity', $project)
  <div class="card config-card mb-4">
    <div class="card-header d-flex align-items-center py-2">
      <h6 class="m-0 text-muted">
        <i class="fas fa-history mr-1" aria-hidden="true"></i> Histórico de alterações
      </h6>
    </div>
    <div class="card-body">
      <p class="mb-3">
        Consulte os registros das alterações realizadas neste projeto e nos itens vinculados a ele.
      </p>
      <a href="{{ route('projects.activity', $project) }}" class="btn btn-sm btn-outline-primary">
        <i class="fas fa-history mr-1" aria-hidden="true"></i> Ver histórico
      </a>
    </div>
  </div>
@endcan
