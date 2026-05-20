@php
  use App\Enums\Task\TaskStatus;

  $showBacklog = isset($project);
  // se for backlog, mostra as tarefas sem responsável,
  // senão mostra todas agrupadas por status
  $backlogTasks = $showBacklog
      ? $tasks->filter(fn($task) => $task->users->isEmpty() && $task->status !== TaskStatus::DONE)
      : collect();
  // agrupa as tarefas por status (incluindo backlog se for o caso)
  $tasksByStatus = ($showBacklog ? $tasks->reject(fn($task) => $task->users->isEmpty()) : $tasks)->groupBy(
      fn($task) => $task->status->value,
  );
@endphp

<div class="d-flex flex-nowrap overflow-auto pb-2" style="gap: 1rem;">
  @if ($showBacklog)
    <div class="flex-shrink-0" style="width: 320px;">
      <div class="card h-100 shadow-sm border-0">
        <div class="card-header d-flex align-items-center justify-content-between py-2">
          <div class="font-weight-bold text-capitalize">A fazer</div>

          <div class="d-flex align-items-center gap-2">
            @include('module-tasks.partials.kanban.kanban-search', ['status' => 'backlog'])
            <span class="badge badge-secondary">{{ $backlogTasks->count() }}</span>
          </div>
        </div>

        <div class="card-body bg-light">
          @forelse ($backlogTasks as $task)
            <div class="mb-2">
              @include('module-tasks.partials.kanban.kanban-task-card')
            </div>
          @empty
            <div class="alert alert-light border text-center text-muted mb-0">
              Nenhuma tarefa sem responsável.
            </div>
          @endforelse
        </div>
      </div>
    </div>
  @endif

  @foreach (TaskStatus::cases() as $status)
    @php
      $statusTasks = $tasksByStatus->get($status->value, collect());
    @endphp

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
  @endforeach
</div>
