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
            transform: translateY(-6px) scale(1.01);
            box-shadow: 0 1.1rem 2.2rem rgba(0, 0, 0, 0.2);
        }

        .task-preview-title {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            word-break: break-word;
            font-size: 1.12rem;
            font-weight: 800;
            line-height: 1.3;
            min-height: 2.91rem; /* 2 linhas: 1.12rem (font-size) * 1.3 (line-height) * 2 (linhas) = 2.912rem */
            color: #1f2937;
        }

        .task-preview-project {
            font-size: 0.9rem;
            color: #6b7280;
        }

        .task-preview-footer {
            font-size: 0.85rem;
        }
    </style>
@endonce

<div
    class="card mb-3 shadow-sm position-relative task-preview-card border-left-{{ $task->priority instanceof \App\Enums\Task\TaskPriority ? str_replace('badge-', '', $task->priority->color()) : 'secondary' }}">

    <div class="card-body p-3 d-flex flex-column h-100">

        {{-- CABEÇALHO: Título e Status --}}
        <div class="d-flex justify-content-between align-items-start mb-2">
            <h6 class="m-0 pr-2 task-preview-title" title="{{ $task->title }}">
                {{ $task->title }}
            </h6>
            <span class="badge {{ $task->status->color() }} text-nowrap shadow-sm">
                {{ $task->status->label() }}
            </span>
        </div>

        {{-- CORPO: Projeto --}}
        <div class="task-preview-project mb-2">
            <i class="fas fa-folder-open mr-1"></i>
            {{ $task->project?->name ?? 'Sem projeto vinculado' }}
        </div>

        {{-- RODAPÉ: Prioridade/Label e Datas --}}
        <div class="d-flex justify-content-between align-items-end mt-auto task-preview-footer">

            {{-- Lado Esquerdo do Rodapé (Badges) --}}
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

            {{-- Lado Direito do Rodapé (Datas) --}}
            <div class="text-muted text-right text-nowrap pl-2">
                <i class="far fa-calendar-alt mr-1"></i>

                <span title="Data de Início">
                    {{ $task->start_date ? \Carbon\Carbon::parse($task->start_date)->format('d/m/Y') : '--/--/----' }}
                </span>

                <i class="fas fa-arrow-right mx-1" style="font-size: 0.7em; color: #adb5bd;"></i>

                <span title="Prazo de Entrega"
                    class="font-weight-bold {{ $task->due_date && \Carbon\Carbon::parse($task->due_date)->isPast() && $task->status->value !== \App\Enums\Task\TaskStatus::DONE->value ? 'text-danger' : 'text-dark' }}">
                    {{ $task->due_date ? \Carbon\Carbon::parse($task->due_date)->format('d/m/Y') : '--/--/----' }}
                </span>
            </div>

        </div>

        <a href="{{ route('tasks.show', $task->id) }}" class="stretched-link"
            aria-label="Acessar tarefa {{ $task->title }}"></a>
    </div>
</div>
