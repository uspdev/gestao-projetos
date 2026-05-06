<div class="card mb-4 shadow-sm">
  <div class="card-header h5">
    <i class="fas fa-tasks"></i> Tarefas
    @include('tasks.partials.create-task-btn')
    @include('tasks.partials.toggle-layout-btn', ['view' => $view])
    @include('tasks.partials.show-done-btn')
  </div>

  <div class="card-body">
    @include('tasks.partials.kanban.kanban', ['showDone' => $showDone])
  </div>
</div>
