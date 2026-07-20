@php
  $label = 'Tarefas';
  if (($context ?? null) === 'parent') {
      $project = $project->parent;
      $label = '';
  }
  $href = route('projects.tasks.index', $project);
  $btnClass = str_contains(Route::currentRouteName(), 'projects.tasks') ? 'btn-secondary' : 'btn-outline-secondary';
@endphp
<a href="{{ $href }}" class="btn btn-sm position-relative {{ $btnClass }}" title="Tarefas">
  <i class="fas fa-tasks"></i> {{ $label }}
  @if ($project->getIncompleteTasksCount() > 0)
    <span class="badge badge-pill badge-warning" style="position: absolute; top: -8px; right: -8px;">
      {{ $project->getIncompleteTasksCount() }}
    </span>
  @endif
</a>
