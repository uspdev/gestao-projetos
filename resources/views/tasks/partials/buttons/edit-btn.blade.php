@can('update', $task)
  <button type="button" class="btn btn-outline-primary btn-sm py-0" data-toggle="modal" data-target="#modalTaskForm"
    data-task-modal="task-form" data-mode="edit" data-task-id="{{ $task->id }}"
    data-action="{{ route('tasks.update', $task->id) }}" data-title="{{ e($task->title) }}"
    data-status="{{ $task->status?->value }}" data-priority="{{ $task->priority?->value }}"
    data-start-date="{{ $task->start_date ? $task->start_date->format('Y-m-d') : '' }}"
    data-due-date="{{ $task->due_date ? $task->due_date->format('Y-m-d') : '' }}"
    data-description="{{ e($task->description) }}" data-tags='@json($task->tags->pluck('id')->values())'>
    <i class="fas fa-edit"></i>
  </button>
@endcan
