@can('update', [$meeting, $project])
  <a href="{{ route('projects.meetings.edit', [$project, $meeting]) }}" class="btn btn-sm btn-outline-primary py-0"
    aria-label="Editar reunião" title="Editar reunião">
    <i class="fas fa-edit" aria-hidden="true"></i>
  </a>
@endcan
