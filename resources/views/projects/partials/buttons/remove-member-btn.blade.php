<form method="POST" action="{{ route('projects.members.destroy', [$project, $user]) }}"
  onsubmit="return confirm('Deseja remover este membro do projeto?');">
  @csrf
  @method('DELETE')
  <button type="submit" class="btn btn-sm btn-outline-danger py-0" title="Remover membro">
    <i class="fas fa-trash"></i>
  </button>
</form>
