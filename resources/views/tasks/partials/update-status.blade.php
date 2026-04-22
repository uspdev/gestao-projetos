@can('update', $task)
    <div class="dropdown">
        <button class="btn btn-sm p-0 border-0 bg-transparent dropdown-toggle" type="button"
            id="task-status-dropdown-{{ $task->id }}" data-toggle="dropdown" aria-haspopup="true"
            aria-expanded="false" title="Alterar status da task">
            <span class="badge {{ $task->status->color() }} p-2" style="font-size: 1rem;">
                {{ $task->status->label() }}
            </span>
        </button>
        <div class="dropdown-menu dropdown-menu-right p-2" aria-labelledby="task-status-dropdown-{{ $task->id }}">
            @foreach (\App\Enums\Task\TaskStatus::cases() as $status)
                <form method="POST" action="{{ route('tasks.updateTaskStatus', $task) }}" class="mb-1">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="{{ $status->value }}">
                    <button type="submit" class="btn btn-sm btn-block text-left"
                        @disabled($task->status->value === $status->value)>
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
    <span class="badge {{ $task->status->color() }} p-2" style="font-size: 1rem;">
        {{ $task->status->label() }}
    </span>
@endcan
