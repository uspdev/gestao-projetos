{{-- resources/views/components/task-card.blade.php --}}

@props(['task', 'showProject' => true, 'showDuplicate' => true])

@php
  $href = route('tasks.show', $task);

  $status = $task->status;
  $priority = $task->priority;

  $tags = $task->tagsWithType('tasks');

  $dueDateIsLate =
      $task->due_date &&
      \Carbon\Carbon::parse($task->due_date)->isPast() &&
      $task->status->value !== \App\Enums\Task\TaskStatus::DONE->value;

@endphp

@pushOnce('styles')
  <style>
    .task-card {
      transition: transform .2s ease, box-shadow .2s ease;
      border-radius: .75rem;
      border-color: #d6d8db;
    }

    .task-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 .75rem 1.5rem rgba(0, 0, 0, .12);
    }

    .task-card__title {
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
      line-height: 1.3;
    }
  </style>
@endPushOnce

<div id="{{ deep_link_fragment($task) }}" tabindex="-1" data-deep-link-target
  {{ $attributes->class(['card h-100 task-card entity-card entity-card--task mb-3 shadow-sm']) }}>
  <div class="card-body d-flex flex-column">

    {{-- Header --}}
    <div class="d-flex align-items-start mb-2">
      <div class="d-flex align-items-center flex-wrap flex-grow-1 min-width-0" style="gap: 0.35rem;">
        <h6 class="task-card__title font-weight-bold text-dark mb-0">
          {{ $task->title }}
        </h6>
        @if ($status)
          <span class="badge badge-{{ $status->color() }}">
            {{ $status->label() }}
          </span>
        @endif
      </div>

      @if ($showDuplicate)
        <div class="ml-1 d-flex align-items-center position-relative" style="z-index: 2;">
          @include('module-tasks.partials.buttons.duplicate-btn')
        </div>
      @endif
    </div>

    {{-- Project --}}

    <div class="small text-muted mb-2">
      <i class="fas fa-folder-open mr-1"></i>
      {{ $task->project?->name ?? 'Sem projeto vinculado' }}
    </div>


    {{-- Tags --}}
    @if ($tags->isNotEmpty())
      <div class="d-flex flex-wrap mb-3" style="gap:.25rem;">
        @foreach ($tags->take(3) as $tag)
          <span class="badge badge-light border text-muted">
            <i class="fas fa-tag mr-1"></i>
            {{ $tag->name }}
          </span>
        @endforeach

        @if ($tags->count() > 3)
          <span class="badge badge-light border text-muted">
            +{{ $tags->count() - 3 }}
          </span>
        @endif
      </div>
    @endif

    {{-- Footer --}}
    <div class="d-flex justify-content-between align-items-center mt-auto">
      <span>
        @if ($priority)
          <span class="badge badge-{{ $priority->color() }}">
            <i class="fas fa-flag mr-1"></i>
            {{ $priority->label() }}
          </span>
        @endif
      </span>
      <small class="text-nowrap text-muted">
        <x-local-date :date="$task->start_date" />
        <i class="fas fa-arrow-right mx-1"></i>
        <x-local-date :date="$task->due_date" :overdue="$dueDateIsLate" />
      </small>
    </div>

    <a href="{{ $href }}" class="stretched-link" aria-label="Acessar tarefa {{ $task->title }}">
    </a>

  </div>
</div>
