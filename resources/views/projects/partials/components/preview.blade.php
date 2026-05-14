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
          class="btn btn-sm {{ $isPinned ? 'btn-warning text-dark' : 'btn-outline-secondary' }} shadow-sm text-nowrap">
          <i class="fas fa-thumbtack mr-1"></i>
        </button>
      </form>
    </x-slot:action>
  @endif
</x-card.preview>
