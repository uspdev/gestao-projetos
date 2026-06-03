@extends('projects.layouts.project')

@section('title', $title . ' | Tarefas')

@section('project-content')
  @php
    $tasksMine = session('tasks_mine', '0');
    $tasksCount = $tasksCount ?? $project->tasks->count();
  @endphp

  <div class="card shadow-sm">
    <div class="card-header h5 py-2 d-flex justify-content-start gap-2" style="background-color: lightCyan;">
      <a href="{{ route('projects.tasks.index', $project) }}" class="text-decoration-none text-dark">
        <i class="fas fa-tasks"></i> {{ $tasksMine ? 'Minhas' : 'Todas as' }} tarefas
      </a>
      <span id="project-tasks-count" class="badge badge-pill badge-secondary">{{ $tasksCount }}</span>
      @include('module-tasks.partials.buttons.create-task-btn')

      @section('task-header') @show

      @include('module-tasks.partials.buttons.search-task-form')
    </div>

    <div class="card-body p-2">
      @yield('task-content')
    </div>

  </div>
@endsection

@push('modals')
  @include('module-tasks.partials.components.task-form-modal')
@endpush

@push('scripts')
  <script>
    (function() {
      const badge = document.getElementById('project-tasks-count');
      if (!badge) return;

      function isVisible(el) {
        return !!(el && el.offsetParent);
      }

      function countKanbanTasks() {
        const tasks = document.querySelectorAll('.kanban-task');
        if (!tasks.length) return null;
        let count = 0;
        tasks.forEach(task => {
          if (isVisible(task)) count++;
        });
        return count;
      }

      function countTableRows() {
        const table = document.querySelector('table.datatable-simples');
        if (!table) return null;

        if (window.jQuery && jQuery.fn && jQuery.fn.dataTable && jQuery.fn.dataTable.isDataTable(table)) {
          const dt = jQuery(table).DataTable();
          return dt.rows({
            filter: 'applied'
          }).count();
        }

        const rows = table.querySelectorAll('tbody tr');
        let count = 0;
        rows.forEach(row => {
          if (isVisible(row)) count++;
        });
        return count;
      }

      function updateCount() {
        const kanbanCount = countKanbanTasks();
        const nextCount = kanbanCount !== null ? kanbanCount : countTableRows();
        if (nextCount !== null) {
          badge.textContent = nextCount;
        }
      }

      window.updateProjectTasksCount = updateCount;

      if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', updateCount);
      } else {
        updateCount();
      }

      if (window.jQuery) {
        jQuery(function() {
          const table = jQuery('table.datatable-simples');
          if (table.length) {
            table.on('search.dt draw.dt', updateCount);
          }
        });
      }
    })();
  </script>
@endpush
