@can('update', $task)
  @php
  // Na view de show, o $task não tem os tagsIds carregados
    if (!isset($taskTagIds)) {
        $taskTagIds = $task->project?->tasksTagsIds($task->newCollection([$task]))[$task->id] ?? [];
    }
  @endphp

  <button type="button" class="btn btn-outline-primary btn-sm py-0" data-toggle="modal" data-target="#modalTaskForm"
    data-task-modal="task-form" data-mode="edit" data-task-id="{{ $task->id }}" data-action="{{ route('tasks.updateInfo', $task->id) }}"
    data-title="{{ e($task->title) }}" data-status="{{ $task->status?->value }}"
    data-priority="{{ $task->priority?->value }}"
    data-start-date="{{ $task->start_date ? $task->start_date->format('Y-m-d') : '' }}"
    data-due-date="{{ $task->due_date ? $task->due_date->format('Y-m-d') : '' }}"
    data-tags='@json($taskTagIds)'>
    <i class="fas fa-edit"></i>
  </button>
@endcan
