@php
    $hasPriority = $task->priority instanceof \App\Enums\Task\TaskPriority;
    $hasLabel = $task->label instanceof \App\Enums\Task\TaskLabel;

    $borderColor = $hasPriority ? str_replace('badge-', '', $task->priority->color()) : 'secondary';
@endphp

<div class="card mb-2 border-left-{{ $borderColor }}">
    <div class="card-body py-2">
        <div class="d-flex justify-content-between align-items-center mb-1">
            <h6 class="m-0">
                <a href="{{ route('tasks.show', $task->id) }}" class="text-dark">
                    {{ $task->title }}
                </a>

                {{-- Label --}}
                @if ($hasLabel)
                    <span class="badge {{ $task->label->color() }} ml-1">
                        {{ $task->label->label() }}
                    </span>
                @endif
            </h6>

            {{-- Status --}}
            <span class="badge {{ $task->status->color() }}">
                {{ $task->status->label() }}
            </span>
        </div>

        <div class="d-flex justify-content-between align-items-center mt-2">
            {{-- Data --}}
            <small class="text-muted">
                <i class="far fa-calendar-alt"></i> Prazo:
                {{ $task->due_date ? \Carbon\Carbon::parse($task->due_date)->format('d/m/Y') : 'Não definido' }}
            </small>

            {{-- Priority --}}
            @if ($hasPriority)
                <small class="text-muted">
                    Prioridade: <span class="badge {{ $task->priority->color() }}">{{ $task->priority->label() }}</span>
                </small>
            @endif
        </div>
    </div>
</div>
