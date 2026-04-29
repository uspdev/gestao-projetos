@php
  use App\Enums\Task\TaskStatus;

  $tasksByStatus = $tasks->groupBy(fn($task) => $task->status->value);
  $columnLabels = [
      TaskStatus::TO_DO->value => 'a fazer',
      TaskStatus::IN_PROGRESS->value => 'em andamento',
      TaskStatus::IN_REVIEW->value => 'em revisão',
      TaskStatus::HOLD->value => 'em espera',
      TaskStatus::DONE->value => 'concluída',
  ];
@endphp

<div class="d-flex flex-nowrap overflow-auto pb-2" style="gap: 1rem;">
  @foreach (TaskStatus::cases() as $status)
    @php
      $statusTasks = $tasksByStatus->get($status->value, collect());
    @endphp

    <div class="flex-shrink-0" style="width: 320px;">
      <div class="card h-100 shadow-sm border-0">
        <div class="card-header bg-white d-flex align-items-center justify-content-between py-3">
          <div class="font-weight-bold text-capitalize">{{ $columnLabels[$status->value] ?? $status->label() }}</div>
          <span class="badge {{ $status->color() }}">{{ $statusTasks->count() }}</span>
        </div>

        <div class="card-body bg-light">
          @forelse ($statusTasks as $task)
            <div class="mb-2">
              @include('user-tasks.partials.kanban-task-card', ['task' => $task])
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
