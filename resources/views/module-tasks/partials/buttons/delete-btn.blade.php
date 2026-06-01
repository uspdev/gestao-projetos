@can('delete', $task)
  <form method="POST" action="{{ route('tasks.destroy', $task) }}" class="d-inline-block ml-1"
    onsubmit="return confirm('Deseja realmente excluir esta tarefa? Esta ação não pode ser desfeita.');">
    @csrf
    @method('DELETE')
    <button type="submit" class="btn btn-outline-danger btn-sm py-0">
      <i class="fas fa-trash"></i>
    </button>
  </form>
@endcan
