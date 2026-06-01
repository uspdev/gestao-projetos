@php
  $project = $task->project;
@endphp

<div class="card-header h4 d-flex justify-content-between align-items-center gap-2">
  <div class="d-flex align-items-center gap-2">
    <a href="{{ route('projects.index') }}" class="text-decoration-none text-secondary fw-medium">
      <i class="fas fa-home"></i>
    </a>
    <x-separator />
    @if ($project->isSubproject() && $project->parent)
      <a href="{{ route('projects.show', $project->parent) }}" class="text-decoration-none text-secondary fw-medium">
        {{ $project->parent->name }}
      </a>
      <x-separator />
    @endif

    <a href="{{ route('projects.show', $project) }}" class="text-decoration-none text-secondary fw-medium">
      {{ $project->name }}
    </a>

    <x-separator />
    <a href="{{ route('projects.tasks.index', $project) }}" class="text-decoration-none text-secondary fw-medium">
      Tarefas
    </a>
    @include('module-tasks.partials.buttons.create-task-btn', ['project' => $project])
  </div>
</div>
