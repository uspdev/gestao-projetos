@php
  $user = auth()->user();
  $userRole = $user ? $project->userRole($user) : null;
  $allTags = $project->tagsWithType('projects');
  $projectType = $project->projectType?->name;
  $canPin = (bool) $userRole;
  $isPinned = $canPin && $project->isPinnedBy($user);
@endphp

<x-card.preview class="mb-3 shadow-sm border-left-primary" href="{{ route('projects.show', $project) }}"
  aria-label="Acessar projeto {{ $project->name }}" :title="$project->name" title-variant="project" :status-label="$project->status->label()"
  :status-class="$project->status->color()" :subproject-label="$project->isSubproject() ? 'Subprojeto' : null" subproject-class="badge-light border text-muted" :project-type="$projectType"
  :role-label="$userRole?->label() ?? 'Sem vínculo'" :role-class="$userRole?->color() ?? 'badge-light border text-muted'" :tags="$allTags" :tags-limit="2">
  @if ($canPin)
    <x-slot:action>
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
    </x-slot:action>
  @endif
</x-card.preview>
