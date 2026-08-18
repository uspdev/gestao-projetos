@can('viewActivity', $project)
  <a href="{{ route('projects.activity', $project) }}"
    class="btn btn-sm {{ request()->routeIs('projects.activity') ? 'btn-warning' : 'btn-outline-warning' }}"
    title="Histórico de alterações" aria-label="Histórico de alterações">
    <i class="fas fa-history" aria-hidden="true"></i>
  </a>
@endcan
