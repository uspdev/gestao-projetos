<div class="flex-shrink-0" style="width: 320px;">
  <div class="card h-100 shadow-sm border-0">
    <div class="card-header d-flex align-items-center justify-content-between py-2">
      <div class="font-weight-bold text-capitalize">A fazer</div>

      <div class="d-flex align-items-center gap-2">
        @include('module-tasks.partials.kanban.kanban-search', ['status' => 'backlog'])
        <span class="badge badge-secondary">{{ $backlogTasks->count() }}</span>
      </div>
    </div>

    <div class="card-body bg-light m-0 p-0">
      @forelse ($backlogTasks as $task)
        @include('module-tasks.partials.kanban.kanban-task-card')
      @empty
        <div class="alert alert-light border text-center text-muted mb-0">
          Nenhuma tarefa sem responsável.
        </div>
      @endforelse
    </div>
  </div>
</div>

<div class="flex-shrink-0" style="width: 320px;">
  <div class="card h-100 shadow-sm border-0">
    <div class="card-header d-flex align-items-center justify-content-between py-2">
      <div class="font-weight-bold text-capitalize">{{ $status->label() }}</div>

      <div class="d-flex align-items-center gap-2">
        @include('module-tasks.partials.kanban.kanban-search')
        <span class="badge {{ $status->color() }}">{{ $statusTasks->count() }}</span>
      </div>
    </div>

    <div class="card-body bg-light">
      @forelse ($statusTasks as $task)
        <div class="mb-2">
          @include('module-tasks.partials.kanban.kanban-task-card')
        </div>
      @empty
        <div class="alert alert-light border text-center text-muted mb-0">
          Nenhuma tarefa neste status.
        </div>
      @endforelse
    </div>
  </div>
</div>
