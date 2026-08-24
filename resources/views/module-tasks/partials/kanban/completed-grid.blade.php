@php
  $completedTasks = $tasksByStatus->get(\App\Enums\Task\TaskStatus::DONE->value, collect());
@endphp

@pushOnce('styles')
  <style>
    .kanban-completed-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 1rem;
      max-height: min(65vh, 42rem);
      overflow-y: auto;
      overscroll-behavior-y: auto;
      padding-right: .25rem;
    }

    .kanban-completed-grid .kanban-task {
      margin-bottom: 0 !important;
    }
  </style>
@endPushOnce

@if ($completedTasks->isNotEmpty())
  <div class="kanban-completed-grid" tabindex="0" aria-label="Tarefas concluídas">
    @foreach ($completedTasks as $task)
      @include('module-tasks.partials.kanban.kanban-task-card')
    @endforeach
  </div>
@else
  <div class="alert alert-light border text-center text-muted mb-0">
    Nenhuma tarefa concluída.
  </div>
@endif
