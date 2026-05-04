{{-- Card: Lista de Tasks --}}
<div class="card mb-4 shadow-sm">
  <div class="card-header h5">
    <a href="{{ route('projects.tasks.index', $project) }}?view=list">
      <i class="fas fa-tasks"></i> Tarefas
    </a>
    <span class="badge badge-pill badge-primary">{{ count($project->tasks) }}</span>
    @include('tasks.partials.create-task-btn')
    @include('tasks.partials.show-done-btn')
  </div>

  <div class="card-body">
    @forelse ($project->tasks as $task)
      @include('partials.tasks.preview', ['task' => $task, 'showProject' => false])
    @empty
      <div class="alert alert-secondary text-center p-3 shadow-sm mb-0" role="alert">
        <i class="fas fa-clipboard-list fa-2x text-muted mb-2"></i>
        <h6 class="text-muted m-0">Nenhuma tarefa.</h6>
      </div>
    @endforelse
  </div>
</div>
