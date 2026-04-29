<div class="card-header h4 d-flex justify-content-between align-items-center gap-2">
  <div class="d-flex align-items-center gap-2">
    <a href="{{ route('projects.index') }}" class="text-decoration-none text-secondary fw-medium">
      Meus Projetos
    </a>
    <i class="fas fa-angle-right text-muted"></i>
    <a href="{{ route('projects.show', $task->project_id) }}" class="text-decoration-none text-secondary fw-medium">
      {{ $task->project->name }}
    </a>
    <i class="fas fa-angle-right text-muted"></i>
    <a href="{{ route('projects.tasks.index', $task->project) }}" class="text-decoration-none text-secondary fw-medium">
      Tarefas
    </a>
    <i class="fas fa-angle-right text-muted"></i>
    <span class="text-dark fw-semibold">{{ $task->title }}</span>
  </div>

  <div class="d-flex align-items-center gap-2">
    @include('tasks.partials.create-task-btn', ['project' => $task->project])
    @includeWhen(auth()->user()->can('update', $task), 'tasks.partials.update-status', ['task' => $task])
  </div>
</div>
