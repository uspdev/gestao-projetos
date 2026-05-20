@can('create', [\App\Models\Meeting::class, $project])
  <a href="{{ route('projects.meetings.create', $project) }}" class="btn btn-sm btn-outline-success py-0">
    <i class="fas fa-plus"></i> Nova reuniao
  </a>
@endcan
