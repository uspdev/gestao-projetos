@php
  $allTags = $task->tagsWithType('tasks');
  $dueDateIsLate =
      $task->due_date &&
      \Carbon\Carbon::parse($task->due_date)->isPast() &&
      $task->status->value !== \App\Enums\Task\TaskStatus::DONE->value;
@endphp

<x-card.preview
  class="mb-3 shadow-sm border-left-{{ $task->priority instanceof \App\Enums\Task\TaskPriority ? $task->priority->color() : 'secondary' }}"
  href="{{ route('tasks.show', $task->id) }}" aria-label="Acessar tarefa {{ $task->title }}" :title="$task->title"
  title-variant="task" title-tag="h6" title-class="pr-2" :status-label="$task->status->label()" :status-class="'badge-' . $task->status->color()" :show-project="$showProject ?? true"
  :project-name="$task->project?->name ?? 'Sem projeto vinculado'" :footer-priority-label="$task->priority instanceof \App\Enums\Task\TaskPriority ? $task->priority->label() : null" :footer-priority-class="$task->priority instanceof \App\Enums\Task\TaskPriority ? 'badge-' . $task->priority->color() : null" :footer-tags="$allTags" :footer-tags-limit="3" :start-date="$task->start_date"
  :due-date="$task->due_date" :due-date-is-late="$dueDateIsLate" />
