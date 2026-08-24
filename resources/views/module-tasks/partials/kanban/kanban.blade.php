@if (session('tasks_done'))
  @include('module-tasks.partials.kanban.completed-grid')
@else
  @pushOnce('styles')
    <style>
      .kanban-column-body {
        max-height: min(65vh, 42rem);
        overflow-y: auto;
        overscroll-behavior-y: auto;
      }
    </style>
  @endPushOnce

  <div class="d-flex flex-nowrap overflow-auto pb-2" style="gap: 1rem;">
    @foreach ($statuses ?? \App\Enums\Task\TaskStatus::availableForKanban(session('tasks_done')) as $status)
      @php
        $tasks = $tasksByStatus->get($status->value, collect());
      @endphp
      <div class="flex-shrink-0" style="width: 330px;">
        <div class="card shadow-sm border-0">
          <div class="card-header d-flex align-items-center py-2">
            <div class="font-weight-bold text-capitalize">{{ $status->label() }}</div>
            <span class="badge badge-{{ $status->color() }} ml-2">{{ $tasks->count() }}</span>
          </div>

          <div class="card-body bg-light p-2 kanban-column-body" tabindex="0"
            aria-label="Tarefas: {{ $status->label() }}">
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
@endif
