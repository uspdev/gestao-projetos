@php
  use App\Enums\Task\TaskStatus;
  // $tasksDone = session('tasks_done');
  // $showBacklog = isset($project);
@endphp

<div class="d-flex flex-nowrap overflow-auto pb-2" style="gap: 1rem;">
  @foreach ($statuses as $status)
    @php
      $tasks = $tasksByStatus->get($status->value, collect());
    @endphp
    <div class="flex-shrink-0" style="width: 320px;">
      <div class="card h-100 shadow-sm border-0">
        <div class="card-header d-flex align-items-center justify-content-between py-2">
          <div class="font-weight-bold text-capitalize">{{ $status->label() }}</div>

          <div class="d-flex align-items-center gap-2">
            @include('module-tasks.partials.kanban.kanban-search')
            <span class="badge {{ $status->color() }}">{{ $tasks->count() }}</span>
          </div>
        </div>

        <div class="card-body bg-light">
          @forelse ($tasks as $task)
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
