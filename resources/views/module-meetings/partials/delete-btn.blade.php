@can('delete', [$meeting, $project])
  <form method="POST" action="{{ route('projects.meetings.destroy', [$project, $meeting]) }}" class="d-inline-block"
    onsubmit="return confirm('Deseja remover esta reuniao?');">
    @csrf
    @method('DELETE')
    <button type="submit" class="btn btn-outline-danger btn-sm py-0">
      <i class="fas fa-trash"></i>
    </button>
  </form>
@endcan
