@php
  $tasksTagsIds = $project->tasksTagsIds($tasksByStatus);
  $tasksEnabled = $project->isModuleEnabled('tasks');
@endphp

@if (!$tasksEnabled)
  <div class="alert alert-light border text-muted mb-0">
    O módulo de tarefas está desativado para este projeto.
  </div>
@else
  <table class="table table-bordered table-hover task-search-table datatable-simples">
    <thead>
      <tr>
        <th></th>
        <th title="Prioridade">Prio.</th>
        <th>Status</th>
        <th>Início</th>
        <th>Prazo</th>
        <th>Conclusão</th>
        <th>Título</th>
        <th>Responsável</th>
        <th>Tags</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($tasksByStatus as $task)
        <tr data-task-searchable="{{ $task->searchableText() }}">
          <td>
            @php
              $taskTagIds = $tasksTagsIds[$task->id] ?? [];
            @endphp
            @include('module-tasks.partials.buttons.edit-btn')
            @include('module-tasks.partials.buttons.duplicate-btn')
          </td>
          <td>
            @include('module-tasks.partials.priority-badge')
          </td>
          <td>
            @include('module-tasks.partials.status-badge')
          </td>
          <td data-order="{{ $task->start_date?->format('Y-m-d H:i:s') }}">
            <x-local-date :date="$task->start_date" empty="-" />
          </td>
          <td data-order="{{ $task->due_date?->format('Y-m-d H:i:s') }}">
            <x-local-date :date="$task->due_date" :overdue="$task->isOverdue()" empty="-" />
          </td>
          <td data-order="{{ $task->completed_at?->format('Y-m-d H:i:s') }}">
            <x-local-date :date="$task->completed_at" empty="-" />
          </td>
          <td>
            <a href="{{ deep_link('tasks.show', $task) }}" class="text-decoration-none">
              {{ $task->title }}
            </a>
          </td>
          <td>
            {{ $task->users->pluck('name')->implode(', ') }}
          </td>
          <td>
            @foreach ($task->tags as $tag)
              <span class="badge badge-secondary">{{ $tag->name }}</span>
            @endforeach
          </td>

        </tr>
      @endforeach
    </tbody>
  </table>

  <div id="tasks-no-results" class="alert alert-info d-none mb-0">Nenhuma tarefa encontrada para sua busca.</div>
@endif

@pushOnce('styles')
  <style>
    .dataTables_wrapper .dt-top-bar {
      display: none !important;
    }
  </style>
@endPushOnce
