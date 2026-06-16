@pushOnce('styles')
  <style>
    .kanban-task {
      transition: all 0.2s ease;
      cursor: pointer;
      position: relative;
    }

    .kanban-task:hover {
      transform: translateY(-2px);
      box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15);
    }

    .kanban-task:has(.dropdown.show):hover {
      transform: none;
      box-shadow: none;
    }

    .kanban-task .stretched-link::after {
      z-index: 1;
    }

    .kanban-task .dropdown {
      position: relative;
      z-index: 10;
    }

    .kanban-task .dropdown:hover {
      position: relative;
      z-index: 20;
    }

    .kanban-task .dropdown-menu {
      z-index: 20;
    }
  </style>
@endPushOnce

<div class="card border-0 shadow-sm kanban-task mb-2" data-search="{{ $task->searchableText() }}">
  <div class="card-body py-3">
    <a href="{{ route('tasks.show', $task->id) }}" class="stretched-link"></a>

    <div class="d-flex align-items-start justify-content-between gap-2 px-1">
      {{ $task->title }}
      @include('module-tasks.partials.kanban.kanban-update-status')
    </div>

    <div class="text-muted small mb-2">
      <i class="fas fa-folder-open mr-1"></i>
      {{ $task->project?->name ?? 'Sem projeto vinculado' }}
    </div>

    <div class="d-flex align-items-center justify-content-between small text-muted">
      <span>
        <i class="far fa-calendar-alt mr-1"></i>
        @if ($task->completed_at)
          <x-local-date :date="$task->completed_at" /> <i class="fas fa-check"></i>
        @else
          <x-local-date :date="$task->start_date" />
          <i class="fas fa-arrow-right fa-sm mx-1"></i>
          <x-local-date :date="$task->due_date" :overdue="$task->isOverdue()" />
        @endif
      </span>
      @include('module-tasks.partials.priority-badge')
    </div>

    @if ($task->users->isNotEmpty())
      <hr class="my-1" />
      <div class="small">
        <i class="fas fa-users"></i>
        {{ $task->users->sortBy('name')->pluck('name')->implode(', ') }}
      </div>
    @endif
  </div>
</div>
