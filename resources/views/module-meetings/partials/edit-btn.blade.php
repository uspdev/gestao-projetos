@can('update', [$meeting, $project])
  <a href="{{ route('projects.meetings.edit', [$project, $meeting]) }}" class="btn btn-sm btn-outline-primary py-0">
    <i class="fas fa-edit"></i>
  </a>
@endcan
