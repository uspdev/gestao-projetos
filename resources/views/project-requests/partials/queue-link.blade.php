<a href="{{ route('projects.requests.index', $project) }}"
  class="text-decoration-none text-dark d-flex align-items-center gap-2">
  <span class="badge badge-pill badge-warning" data-pending-requests-count>{{ $pendingRequestsCount }}</span>
  <span data-project-requests-title>Solicitações</span>
</a>
