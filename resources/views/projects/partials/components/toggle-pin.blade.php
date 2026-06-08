@php
  $user = auth()->user();
  $isPinned = $project?->isPinnedBy($user) ?? false;
@endphp

<form method="POST" action="{{ route('projects.togglePin', $project) }}" class="position-relative">
  @csrf
  @method('PATCH')

  <button type="submit"
    class="badge badge-pill {{ $isPinned ? 'badge-warning text-dark' : 'badge-secondary' }} border-0 shadow-sm"
    style="cursor: pointer; transition: opacity 0.2s; padding: 0.15rem 0.35rem; font-size: 0.75rem; line-height: 1;"
    onmouseover="this.style.opacity='0.75'" onmouseout="this.style.opacity='1'">
    <i class="fas fa-thumbtack"></i>
  </button>
</form>
