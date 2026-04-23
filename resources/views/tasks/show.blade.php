@extends('layouts.app')

@section('title', 'Detalhes da Tarefa')

@section('content')
    <div class="container-fluid">
        {{-- Breadcrumb simplificado --}}
        <div class="mb-4 d-flex align-items-center">
            <a href="{{ route('users.projects.index', auth()->id()) }}" class="btn btn-sm btn-outline-secondary">
                Meus Projetos
            </a>

            <i class="fas fa-chevron-right text-muted mx-2" style="font-size: 0.8rem;"></i>

            <a href="{{ route('projects.show', $task->project_id) }}" class="btn btn-sm btn-outline-secondary">
                {{ $task->project->name }}
            </a>

            <i class="fas fa-chevron-right text-muted mx-2" style="font-size: 0.8rem;"></i>

            <span class="btn btn-sm btn-outline-secondary font-weight-bold" style="pointer-events: none;">
                {{ $task->title }}
            </span>
        </div>

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                {{ session('error') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Fechar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        <div class="row">
            {{-- COLUNA PRINCIPAL: Título e Descrição --}}
            <div class="col-md-8">
                <div class="card mb-4 shadow-sm">
                    <div class="card-body">
                        {{-- Título --}}
                        <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-4">
                            <h2 class="m-0 text-dark font-weight-bold">{{ $task->title }}</h2>
                            <div>
                                @includeWhen(auth()->user()->can('update', $task), 'tasks.edit', [
                                    'task' => $task,
                                ])
                                @can('delete', $task)
                                    <form method="POST" action="{{ route('tasks.destroy', $task) }}"
                                        class="d-inline-block ml-1"
                                        onsubmit="return confirm('Deseja realmente excluir esta tarefa? Esta ação não pode ser desfeita.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm">
                                            <i class="fas fa-trash"></i> Excluir
                                        </button>
                                    </form>
                                @endcan
                            </div>
                        </div>

                        {{-- Descrição --}}
                        <div class="text-dark text-justify" style="font-size: 1.1rem; line-height: 1.6;">
                            @if ($task->description)
                                {!! nl2br(e($task->description)) !!}
                            @else
                                <div class="text-center text-muted p-5 bg-light rounded">
                                    <i class="fas fa-align-left fa-3x mb-3 text-secondary"></i>
                                    <h5>Sem descrição</h5>
                                    <p class="mb-0">Nenhuma descrição foi fornecida para esta tarefa.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            {{-- COLUNA LATERAL (Direita): Metadados e Responsáveis --}}
            <div class="col-md-4">

                {{-- Informações --}}
                <div class="card mb-4 shadow-sm border-top-primary">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted font-weight-bold">Status atual</span>
                            @include('tasks.partials.update-status', ['task' => $task])
                        </div>
                    </div>
                    <div class="card-body p-3">
                        <ul class="list-unstyled m-0">

                            {{-- Prioridade e Tag --}}
                            <li class="mb-3 border-bottom pb-2">
                                <div class="row no-gutters">
                                    {{-- Prioridade --}}
                                    <div class="col-6 border-right pr-2 d-flex align-items-center">
                                        <span class="text-muted small mr-2">Prioridade:</span>
                                        @if ($task->priority instanceof \App\Enums\Task\TaskPriority)
                                            <span
                                                class="badge {{ $task->priority->color() }}">{{ $task->priority->label() }}</span>
                                        @else
                                            <span class="text-muted font-italic small">-</span>
                                        @endif
                                    </div>
                                    {{-- Tag --}}
                                    <div class="col-6 pl-2 d-flex align-items-center">
                                        <span class="text-muted small mr-2">Tag:</span>
                                        @forelse ($task->tagsWithType('tasks') as $tag)
                                            <span class="badge {{ $tag->color }}">
                                                <i class="fas fa-tag mr-1"></i>{{ $tag->name }}
                                            </span>
                                        @empty
                                            <span class="text-muted font-italic small">-</span>
                                        @endforelse
                                    </div>
                                </div>
                            </li>

                            {{-- Datas --}}
                            <li class="mb-0">
                                <div class="row no-gutters">
                                    {{-- Data de Início --}}
                                    <div class="col-6 border-right pr-2 d-flex align-items-center">
                                        <span class="text-muted small mr-2">Início:</span>
                                        <span class="font-weight-bold">
                                            @if ($task->start_date)
                                                <time class="local-date"
                                                    datetime="{{ $task->start_date->format('Y-m-d') }}">{{ $task->start_date->format('Y-m-d') }}</time>
                                            @else
                                                --/--/----
                                            @endif
                                        </span>
                                    </div>
                                    {{-- Prazo (Due Date) --}}
                                    <div class="col-6 pl-2 d-flex align-items-center">
                                        <span class="text-muted small mr-2">Prazo:</span>
                                        <span
                                            class="font-weight-bold {{ $task->due_date && \Carbon\Carbon::parse($task->due_date)->isPast() && $task->status->value !== \App\Enums\Task\TaskStatus::DONE->value ? 'text-danger' : 'text-dark' }}">
                                            @if ($task->due_date)
                                                <time class="local-date"
                                                    datetime="{{ $task->due_date->format('Y-m-d') }}">{{ $task->due_date->format('Y-m-d') }}</time>
                                            @else
                                                --/--/----
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                {{-- Responsáveis --}}
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h6 class="m-0 text-muted">
                            <i class="fas fa-users mr-1"></i> Responsáveis
                        </h6>
                        @includeWhen(auth()->user()->can('storeAssignee', $task),
                            'partials.tasks.add-assignee',
                            [
                                'task' => $task,
                            ]
                        )
                    </div>
                    <ul class="list-group list-group-flush">
                        @forelse($task->users as $user)
                            @include('users.preview', [
                                'user' => $user,
                                'project' => $task->project,
                                'task' => $task,
                                'canManageTaskAssignees' => auth()->user()->can('storeAssignee', $task),
                            ])
                        @empty
                            <li class="list-group-item text-muted font-italic small text-center py-3">
                                Nenhum usuário atribuído.
                            </li>
                        @endforelse
                    </ul>
                </div>

            </div>
        </div>

        {{-- Reabre o modal caso haja erro de validação na edição (Vanilla JS) --}}
        @can('update', $task)
            @if ($errors->any() && old('_method') === 'PUT')
                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const editBtn = document.querySelector('[data-target="#modalEditarTask"]');
                        if (editBtn) editBtn.click();
                    });
                </script>
            @endif
        @endcan


    </div>
@endsection
