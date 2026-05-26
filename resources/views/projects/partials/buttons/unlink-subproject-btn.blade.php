<form method="POST" action="{{ route('projects.subprojects.unlink', $project) }}" class="position-relative">
  @csrf
  @method('DELETE')

  <input type="hidden" name="subproject_id" value="{{ $subproject->id }}">

  <button type="submit" class="btn btn-sm btn-outline-danger shadow-sm py-0"
    title="Desvincular subprojeto {{ $subproject->name }}" aria-label="Desvincular subprojeto {{ $subproject->name }}"
    onclick="return confirm('Deseja desvincular este subprojeto?')">
    <i class="fas fa-unlink"></i>
  </button>
</form>
