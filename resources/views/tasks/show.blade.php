@extends('layouts.app')

@section('title', 'Detalhes da Tarefa')

@section('content')
    <div class="container-fluid">
        {{-- Navegação / Breadcrumb Simplificado --}}
        <div class="mb-3">
            <a href="{{ route('projects.show', $task->project_id) }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Voltar para o Projeto
            </a>
        </div>

        {{-- Form de Edição de Task --}}
        <div class="collapse mb-4" id="collapseEditarTask">
            <div class="card card-body border-primary">
                @include('tasks.edit', ['task' => $task])
            </div>
        </div>

        <div class="row">
            {{-- COLUNA PRINCIPAL: Título e Descrição --}}
            <div class="col-md-8">
                <div class="card mb-4 shadow-sm">
                    <div class="card-body">
                        {{-- Título --}}
                        <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-4">
                            <h2 class="m-0 text-dark font-weight-bold">{{ $task->title }}</h2>
                            <div>
                                <button class="btn btn-outline-primary btn-sm" data-toggle="collapse"
                                    data-target="#collapseEditarTask">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
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
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="text-muted font-weight-bold">Status atual</span>
                            <span class="badge {{ $task->status->color() }} p-2" style="font-size: 1rem;">
                                {{ $task->status->label() }}
                            </span>
                        </div>

                        <form method="POST" action="{{ route('tasks.updateTaskStatus', $task) }}"
                            class="d-flex align-items-end">
                            @csrf
                            @method('PATCH')
                            <div class="form-group mb-0 flex-grow-1 mr-2">
                                <label for="task-status" class="small text-muted font-weight-bold mb-1">Alterar
                                    status</label>
                                <select id="task-status" name="status" class="form-control form-control-sm">
                                    @foreach (\App\Enums\Task\TaskStatus::cases() as $status)
                                        <option value="{{ $status->value }}" @selected(old('status', $task->status->value) === $status->value)>
                                            {{ $status->label() }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('status')
                                    <small class="text-danger d-block mt-1">{{ $message }}</small>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="fas fa-save mr-1"></i> Salvar
                            </button>
                        </form>
                    </div>
                    <div class="card-body p-3">
                        <ul class="list-unstyled m-0">
                            {{-- Projeto --}}
                            <li class="mb-3 border-bottom pb-2 d-flex align-items-center">
                                <span class="text-muted small font-weight-bold mr-2">Projeto:</span>
                                <a href="{{ route('projects.show', $task->project_id) }}"
                                    class="small font-weight-bold text-primary" title="Acessar página do projeto">
                                    <u>{{ $task->project->name ?? 'Acessar' }}</u>
                                    <i class="fas fa-external-link-alt ml-1" style="font-size: 0.85em;"></i>
                                </a>
                            </li>

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
                                        @if ($task->label instanceof \App\Enums\Task\TaskLabel)
                                            <span
                                                class="badge {{ $task->label->color() }}">{{ $task->label->label() }}</span>
                                        @else
                                            <span class="text-muted font-italic small">-</span>
                                        @endif
                                    </div>
                                </div>
                            </li>

                            {{-- Datas --}}
                            <li class="mb-0">
                                <div class="row no-gutters">
                                    {{-- Data de Início --}}
                                    <div class="col-6 border-right pr-2 d-flex align-items-center">
                                        <span class="text-muted small mr-2">Início:</span>
                                        <span class="text-dark small font-weight-bold">
                                            {{ $task->start_date ? \Carbon\Carbon::parse($task->start_date)->format('d/m/y') : '-' }}
                                        </span>
                                    </div>
                                    {{-- Prazo (Due Date) --}}
                                    <div class="col-6 pl-2 d-flex align-items-center">
                                        <span class="text-muted small mr-2">Prazo:</span>
                                        <span class="text-dark small font-weight-bold">
                                            {{ $task->due_date ? \Carbon\Carbon::parse($task->due_date)->format('d/m/y') : '-' }}
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
                        <button class="btn btn-sm btn-outline-success" title="Atribuir usuário">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    <ul class="list-group list-group-flush">
                        @forelse($task->users as $user)
                            @include('users.preview', ['user' => $user, 'project' => $task->project])
                        @empty
                            <li class="list-group-item text-muted font-italic small text-center py-3">
                                Nenhum usuário atribuído.
                            </li>
                        @endforelse
                    </ul>
                </div>

            </div>
        @endsection
