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
        <div class="preview-card__project mb-2">
            <i class="fas fa-folder-open mr-1"></i>
            {{ $task->project?->name ?? 'Sem projeto vinculado' }}
        </div>
    </x-slot>

    <x-slot name="footer">
        <div class="d-flex align-items-center flex-wrap" style="gap: 0.25rem;">
            @if ($task->priority instanceof \App\Enums\Task\TaskPriority)
                <span class="badge {{ $task->priority->color() }}" title="Prioridade">
                    <i class="fas fa-flag mr-1"></i>{{ $task->priority->label() }}
                </span>
            @endif

            @if ($task->label instanceof \App\Enums\Task\TaskLabel)
                <span class="badge {{ $task->label->color() }}" title="Tag">
                    <i class="fas fa-tag mr-1"></i>{{ $task->label->label() }}
                </span>
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
