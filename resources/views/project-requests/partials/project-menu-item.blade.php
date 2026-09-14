@if (\Illuminate\Support\Facades\Schema::hasTable('project_requests'))
  @can('viewAny', [\App\Models\ProjectRequest::class, $project])
    @php
      $pendingRequestsCount = $project->projectRequests()->where('status', 'pending')->count();
      $buttonClass = str_starts_with(Route::currentRouteName(), 'projects.requests.')
          ? 'btn-secondary'
          : 'btn-outline-secondary';
    @endphp

    <a href="{{ route('projects.requests.index', $project) }}"
      class="btn btn-sm d-flex align-items-center gap-2 {{ $buttonClass }}" title="Solicitações"
      data-project-requests-menu>
      <span class="badge badge-pill badge-warning" data-pending-requests-count>{{ $pendingRequestsCount }}</span>
      <span data-project-requests-title>Solicitações</span>
    </a>
  @endcan
@endif
