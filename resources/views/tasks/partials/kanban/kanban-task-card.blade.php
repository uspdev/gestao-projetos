<div class="card border-0 shadow-sm kanban-task"
  data-search="{{ strtolower($task->title . $task->project?->name . $task->priority?->label()) }}">
  <div class="card-body py-3">
    <div class="d-flex align-items-start justify-content-between gap-2">
      <a href="{{ route('tasks.show', $task->id) }}" class="text-reset text-decoration-none flex-grow-1 pr-2">
        <h6 class="mb-1" style="line-height: 1.2;">
          {{ $task->title }}
        </h6>
      </a>

      @include('tasks.partials.kanban.kanban-update-status', ['task' => $task])
    </div>

    <div class="text-muted small mb-2">
      <i class="fas fa-folder-open mr-1"></i>
      {{ $task->project?->name ?? 'Sem projeto vinculado' }}
    </div>

    <div class="d-flex align-items-center justify-content-between small text-muted">
      <span>
        <i class="far fa-calendar-alt mr-1"></i>
        @if ($task->due_date)
          <time class="local-date"
            datetime="{{ $task->due_date->format('Y-m-d') }}">{{ $task->due_date->format('Y-m-d') }}</time>
        @else
          --/--/----
        @endif
      </span>

      @if ($task->priority instanceof \App\Enums\Task\TaskPriority)
        <span class="badge {{ $task->priority->color() }}">
          {{ $task->priority->label() }}
        </span>
      @endif
    </div>
  </div>

  @include('tasks.partials.date-formatter-script')
</div>
