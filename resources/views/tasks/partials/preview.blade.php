<x-card.preview
  class="mb-3 shadow-sm border-left-{{ $task->priority instanceof \App\Enums\Task\TaskPriority ? str_replace('badge-', '', $task->priority->color()) : 'secondary' }}"
  href="{{ route('tasks.show', $task->id) }}" aria-label="Acessar tarefa {{ $task->title }}">
  <x-slot name="header">
    <h6 class="m-0 pr-2 preview-card__title preview-card__title--task" title="{{ $task->title }}">
      {{ $task->title }}
    </h6>

    <span class="badge {{ $task->status->color() }} text-nowrap shadow-sm">
      {{ $task->status->label() }}
    </span>
  </x-slot>

  <x-slot name="body">
    @if ($showProject ?? true)
      <div class="preview-card__project mb-2">
        <i class="fas fa-folder-open mr-1"></i>
        {{ $task->project?->name ?? 'Sem projeto vinculado' }}
      </div>
    @endif
  </x-slot>

  <x-slot name="footer">
    @php
      $allTags = $task->tagsWithType('tasks');
      $visibleTags = $allTags->take(3);
      $extraCount = max(0, $allTags->count() - $visibleTags->count());
    @endphp

    <div class="d-flex align-items-center flex-wrap" style="gap: 0.25rem; max-height:3.6rem; overflow:hidden;">
      @if ($task->priority instanceof \App\Enums\Task\TaskPriority)
        <span class="badge {{ $task->priority->color() }}" title="Prioridade">
          <i class="fas fa-flag mr-1"></i>{{ $task->priority->label() }}
        </span>
      @endif

      @foreach ($visibleTags as $tag)
        <span class="badge {{ $tag->color }} d-inline-flex align-items-center" title="Tag">
          <i class="fas fa-tag mr-1"></i>
          <span class="d-inline-block text-truncate" style="max-width:8rem;">{{ $tag->name }}</span>
        </span>
      @endforeach

      @if ($extraCount > 0)
        <span class="badge badge-light border text-muted"
          title="+{{ $extraCount }} outras tags">+{{ $extraCount }}</span>
      @endif
    </div>

    <div class="text-muted text-right text-nowrap pl-2" style="font-size: 0.85rem;">
      <i class="far fa-calendar-alt mr-1"></i>

      <span title="Data de Início">
        @if ($task->start_date)
          <time class="local-date"
            datetime="{{ $task->start_date->format('Y-m-d') }}">{{ $task->start_date->format('Y-m-d') }}</time>
        @else
          --/--/----
        @endif
      </span>

      <i class="fas fa-arrow-right mx-1" style="font-size: 0.7em; color: #adb5bd;"></i>

      <span title="Prazo de Entrega"
        class="font-weight-bold {{ $task->due_date && \Carbon\Carbon::parse($task->due_date)->isPast() && $task->status->value !== \App\Enums\Task\TaskStatus::DONE->value ? 'text-danger' : 'text-dark' }}">
        @if ($task->due_date)
          <time class="local-date"
            datetime="{{ $task->due_date->format('Y-m-d') }}">{{ $task->due_date->format('Y-m-d') }}</time>
        @else
          --/--/----
        @endif
      </span>
    </div>
  </x-slot>
</x-card.preview>

@include('tasks.partials.date-formatter-script')
