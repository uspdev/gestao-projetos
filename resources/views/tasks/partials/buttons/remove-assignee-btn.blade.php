<form method="POST" action="{{ route('tasks.assignees.destroy', [$task, $user]) }}"
  onsubmit="return confirm('Deseja remover este responsável da tarefa?');">
  @csrf
  @method('DELETE')
  <button type="submit" class="btn btn-sm btn-outline-danger" title="Remover responsável">
    <i class="fas fa-trash"></i>
  </button>
</form>
