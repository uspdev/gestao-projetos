@if (isset($task) && auth()->user()->can('storeAssignee', $task))
  <form method="POST" action="{{ route('tasks.assignees.destroy', [$task, $user]) }}"
    onsubmit="return confirm('Deseja remover este responsável da tarefa?');">
    @csrf
    @method('DELETE')
    <button type="submit" class="btn btn-sm btn-outline-danger py-0" title="Remover responsável">
      <i class="fas fa-trash"></i>
    </button>
  </form>
@endif

@if (!isset($task) && isset($project) && auth()->user()->can('storeMember', $project))
  <form method="POST" action="{{ route('projects.members.destroy', [$project, $user]) }}"
    onsubmit="return confirm('Deseja remover este membro do projeto?');">
    @csrf
    @method('DELETE')
    <button type="submit" class="btn btn-sm btn-outline-danger py-0" title="Remover membro">
      <i class="fas fa-trash"></i>
    </button>
  </form>
@endif
