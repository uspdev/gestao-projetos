@can('delete', $project)
  <form method="POST" action="{{ route('projects.destroy', $project) }}" class="d-inline-block ml-1"
    onsubmit="return confirm('Deseja realmente excluir este projeto? Esta ação não pode ser desfeita.');">
    @csrf
    @method('DELETE')
    <button type="submit" class="btn btn-outline-danger btn-sm">
      APAGAR
    </button>
  </form>
@endcan
