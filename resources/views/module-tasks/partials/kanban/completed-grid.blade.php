@php
  $completedTasks = $tasksByStatus->get(\App\Enums\Task\TaskStatus::DONE->value, collect());
@endphp

@once
  @section('styles')
    @parent
    <style>
      .kanban-completed-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        gap: 1rem;
      }

      .kanban-completed-grid .kanban-task {
        margin-bottom: 0 !important;
      }
    </style>
  @endsection
@endonce

@if ($completedTasks->isNotEmpty())
  <div class="kanban-completed-grid">
    @foreach ($completedTasks as $task)
      @include('module-tasks.partials.kanban.kanban-task-card')
    @endforeach
  </div>
@else
  <div class="alert alert-light border text-center text-muted mb-0">
    Nenhuma tarefa concluída.
  </div>
@endif
