@once
    <style>
        .task-preview-card {
            cursor: pointer;
            border-radius: 0.9rem;
            overflow: hidden;
            transition: transform 0.24s cubic-bezier(0.22, 1, 0.36, 1),
                box-shadow 0.24s cubic-bezier(0.22, 1, 0.36, 1);
        }

        .task-preview-card:hover {
            transform: translateY(-4px) scale(1.01);
            box-shadow: 0 0.9rem 1.8rem rgba(0, 0, 0, 0.16);
        }

        .task-preview-title {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            word-break: break-word;
            min-height: 2.8rem;
            font-size: 1.12rem;
            font-weight: 700;
            line-height: 1.2;
            color: #1f2937;
        }

        .task-preview-meta {
            min-height: 2rem;
            font-size: 0.95rem;
        }

        .task-preview-meta small {
            font-size: 0.95rem;
        }
    </style>
@endonce

<div
    class="card mb-2 shadow-sm position-relative task-preview-card border-left-{{ $task->priority instanceof \App\Enums\Task\TaskPriority ? str_replace('badge-', '', $task->priority->color()) : 'secondary' }}">
    <div class="card-body p-3">
        <div class="d-flex align-items-start mb-2">
            <h6 class="m-0 mr-2 task-preview-title">{{ $task->title }}</h6>
            <span class="badge {{ $task->status->color() }} mt-0">
                {{ $task->status->label() }}
            </span>
        </div>

        <div class="task-preview-meta">
            @if ($task->label instanceof \App\Enums\Task\TaskLabel)
                <small class="text-muted d-block">
                    <i class="fas fa-tag"></i>
                    <span class="badge {{ $task->label->color() }}">
                        {{ $task->label->label() }}
                    </span>
                </small>
            @endif
        </div>

        <small class="text-muted d-block" style="font-size: 0.95rem;">
            @if ($task->priority instanceof \App\Enums\Task\TaskPriority)
                <span class="badge {{ $task->priority->color() }} mr-2">
                    {{ $task->priority->label() }}
                </span>
            @endif
            <i class="far fa-calendar-alt"></i>
            {{ $task->due_date ? \Carbon\Carbon::parse($task->due_date)->format('d/m/Y') : '-' }}
        </small>

        <a href="{{ route('tasks.show', $task->id) }}" class="stretched-link"
            aria-label="Acessar tarefa {{ $task->title }}"></a>
    </div>
</div>
