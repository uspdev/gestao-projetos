{{-- Index: Lista de Tasks --}}
<div class="card mb-4 shadow-sm">
  <div class="card-header bg-white d-flex justify-content-between align-items-center">
    <h6 class="m-0 text-muted">
      <i class="fas fa-tasks mr-1"></i> Tarefas
    </h6>
    @include('tasks.partials.create-task-btn')
  </div>
  <div class="card-body">
    @include('project-tasks.partials.list', [
        'tasks' => $project->tasks,
        'taskCardColumnClass' => 'col-12',
    ])
  </div>
</div>
