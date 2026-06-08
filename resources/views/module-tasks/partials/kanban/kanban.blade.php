<div class="d-flex flex-nowrap overflow-auto pb-2" style="gap: 1rem;">
  @foreach ($statuses ?? \App\Enums\Task\TaskStatus::availableForKanban(session('tasks_done')) as $status)
    @php
      $tasks = $tasksByStatus->get($status->value, collect());
    @endphp
    <div class="flex-shrink-0" style="width: 320px;">
      <div class="card shadow-sm border-0">
        <div class="card-header d-flex align-items-center justify-content-between py-2">
          <div class="font-weight-bold text-capitalize">{{ $status->label() }}</div>

          <div class="d-flex align-items-center gap-2">
            <span class="badge badge-{{ $status->color() }}">{{ $tasks->count() }}</span>
          </div>
        </div>

        <div class="card-body bg-light p-2">
          @forelse ($tasks as $task)
            @include('module-tasks.partials.kanban.kanban-task-card')
          @empty
            <div class="alert alert-light border text-center text-muted mb-0">
              Nenhuma tarefa neste status.
            </div>
          @endforelse
        </div>
      </div>
    </div>
  @endforeach
</div>
