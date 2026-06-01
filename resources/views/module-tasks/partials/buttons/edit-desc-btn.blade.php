@can('update', $task)
  <button type="button" class="btn btn-outline-primary btn-sm py-0" data-toggle="modal" data-target="#modalTaskDescription"
    data-task-modal="task-description-form" data-mode="edit" data-task-id="{{ $task->id }}"
    data-action="{{ route('tasks.updateDescription', $task->id) }}" data-title="{{ e($task->title) }}"
    data-description="{{ e($task->description) }}">
    <i class="fas fa-edit"></i>
  </button>
@endcan
