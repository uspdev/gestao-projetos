@php
  $user = auth()->user();
  $userRole = $user ? $project->userRole($user) : null;
  $allTags = $project->tagsWithType('projects');
  $tasksCount = $project->tasks_count ?? $project->tasks->count();
@endphp

<x-card.preview class="mb-3 shadow-sm border-left-primary" href="{{ route('projects.show', $project) }}"
  aria-label="Acessar projeto {{ $project->name }}" :title="$project->name"
  title-variant="project" :status-label="$project->status->label()"
  :status-class="$project->status->color()" :tasks-count="$tasksCount"
  :role-label="$userRole?->label() ?? 'Sem vínculo'"
  :role-class="$userRole?->color() ?? 'badge-light border text-muted'"
  :tags="$allTags" :tags-limit="2" />
