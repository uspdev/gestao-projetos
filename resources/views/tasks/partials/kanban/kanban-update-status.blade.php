@can('update', $task)

  <div class="dropdown flex-shrink-0">
    <button class="btn btn-sm p-0 border-0 bg-transparent d-flex align-items-center" type="button" data-toggle="dropdown"
      title="Alterar status da task">
      <span class="d-inline-block {{ $task->status->color() }}" style="width: 15px; height: 15px; border-radius: 50%;">
      </span>
      <i class="fas fa-chevron-down ml-1" style="font-size: 0.6rem; opacity: 0.6;"></i>
    </button>

    <div class="dropdown-menu dropdown-menu-right p-2" aria-labelledby="kanban-task-status-dropdown-{{ $task->id }}">
      @foreach ([\App\Enums\Task\TaskStatus::TO_DO, \App\Enums\Task\TaskStatus::IN_PROGRESS, \App\Enums\Task\TaskStatus::IN_REVIEW, \App\Enums\Task\TaskStatus::HOLD, \App\Enums\Task\TaskStatus::DONE] as $status)
        <form method="POST" action="{{ route('tasks.updateTaskStatus', $task) }}" class="mb-1">
          @csrf
          @method('PATCH')
          <input type="hidden" name="status" value="{{ $status->value }}">
          <button type="submit" class="btn btn-sm btn-block text-left" @disabled($task->status->value === $status->value)>
            <span class="badge {{ $status->color() }}">
              {{ $status->label() }}
            </span>
            @if ($task->status->value === $status->value)
              <small class="text-muted ml-1">(atual)</small>
            @endif
          </button>
        </form>
      @endforeach
    </div>
  </div>
@else
  <span class="badge {{ $task->status->color() }} text-nowrap flex-shrink-0" style="font-size: 0.75rem; line-height: 1;">
    {{ $task->status->label() }}
  </span>
@endcan
