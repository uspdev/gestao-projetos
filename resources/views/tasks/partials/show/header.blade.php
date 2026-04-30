<div class="card-header h4 d-flex justify-content-between align-items-center gap-2">
  <div class="d-flex align-items-center gap-2">
    <a href="{{ route('projects.index') }}" class="text-decoration-none text-secondary fw-medium">
      <i class="fas fa-home"></i>
    </a>
    <i class="fas fa-angle-right text-muted"></i>
    <a href="{{ route('projects.show', $task->project) }}" class="text-decoration-none text-secondary fw-medium">
      {{ $task->project->name }}
    </a>
    <i class="fas fa-angle-right text-muted"></i>
    <a href="{{ route('projects.tasks.index', $task->project) }}" class="text-decoration-none text-secondary fw-medium">
      Tarefas
    </a>
    @include('tasks.partials.create-task-btn', ['project' => $task->project])
  </div>
</div>
