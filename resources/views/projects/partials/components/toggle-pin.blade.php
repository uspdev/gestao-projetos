<form method="POST" action="{{ route('projects.togglePin', $project) }}" class="position-relative">
  @csrf
  @method('PATCH')

  <button type="submit"
    class="badge badge-pill {{ $isPinned ? 'badge-warning text-dark' : 'badge-secondary' }} border-0 shadow-sm"
    style="cursor: pointer; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.75'"
    onmouseout="this.style.opacity='1'">
    <i class="fas fa-thumbtack"></i>
  </button>
</form>
