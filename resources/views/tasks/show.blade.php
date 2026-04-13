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
                                <button type="button" class="btn btn-outline-primary btn-sm" data-toggle="modal" data-target="#modalEditarTask">
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
                        @can('storeAssignee', $task)
                            <button class="btn btn-sm btn-outline-success" title="Atribuir usuário" data-toggle="modal"
                                data-target="#addTaskAssigneeModal">
                                <i class="fas fa-plus"></i>
                            </button>
                        @endcan
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

                @can('storeAssignee', $task)
                    <div class="modal fade" id="addTaskAssigneeModal" tabindex="-1" role="dialog"
                        aria-labelledby="addTaskAssigneeModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered" role="document">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="addTaskAssigneeModalLabel">Adicionar responsável à tarefa</h5>
                                    <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <form method="POST" action="{{ route('tasks.assignees.store', $task) }}">
                                    @csrf
                                    <div class="modal-body">
                                        <div id="task-assignee-empty-state"
                                            class="alert alert-light border text-muted mb-3 d-none"></div>

                                        <div class="form-group mb-0">
                                            <label for="task-assignee-user-id" class="font-weight-bold">Usuário</label>
                                            <select id="task-assignee-user-id" name="user_id"
                                                class="form-control @error('user_id') is-invalid @enderror" disabled>
                                                <option value="">Carregando usuários...</option>
                                            </select>
                                            @error('user_id')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary"
                                            data-dismiss="modal">Cancelar</button>
                                        <button type="submit" class="btn btn-primary" id="task-assignee-confirm-btn"
                                            disabled>
                                            Confirmar
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    @if ($errors->has('user_id'))
                        <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                $('#addTaskAssigneeModal').modal('show');
                            });
                        </script>
                    @endif

                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const modal = document.getElementById('addTaskAssigneeModal');
                            const userSelect = document.getElementById('task-assignee-user-id');
                            const confirmBtn = document.getElementById('task-assignee-confirm-btn');
                            const emptyState = document.getElementById('task-assignee-empty-state');
                            const loadUrl = '{{ route('tasks.assignees.selectable', $task) }}';
                            const oldUserId = '{{ old('user_id', '') }}';

                            if (!modal || !userSelect || !confirmBtn || !emptyState) {
                                return;
                            }

                            let usersLoaded = false;

                            const setSelectLoading = function() {
                                userSelect.innerHTML = '<option value="">Carregando usuários...</option>';
                                userSelect.setAttribute('disabled', 'disabled');
                                confirmBtn.setAttribute('disabled', 'disabled');
                                emptyState.classList.add('d-none');
                                emptyState.textContent = '';
                            };

                            const fillUserSelect = function(users) {
                                userSelect.innerHTML = '<option value="">Selecione...</option>';

                                users.forEach(function(candidate) {
                                    const option = document.createElement('option');
                                    option.value = String(candidate.id);
                                    option.textContent = candidate.name + ' (' + candidate.email + ')';
                                    userSelect.appendChild(option);
                                });

                                if (oldUserId) {
                                    userSelect.value = String(oldUserId);
                                }
                            };

                            const loadSelectableUsers = function() {
                                if (usersLoaded) {
                                    return;
                                }

                                setSelectLoading();

                                fetch(loadUrl, {
                                        headers: {
                                            'X-Requested-With': 'XMLHttpRequest'
                                        }
                                    })
                                    .then(function(response) {
                                        if (!response.ok) {
                                            throw new Error('Falha ao carregar usuários.');
                                        }

                                        return response.json();
                                    })
                                    .then(function(users) {
                                        if (!Array.isArray(users) || users.length === 0) {
                                            userSelect.innerHTML =
                                                '<option value="">Nenhum usuário disponível</option>';
                                            emptyState.textContent =
                                                'Não há usuários disponíveis para atribuir a esta tarefa.';
                                            emptyState.classList.remove('d-none');
                                            return;
                                        }

                                        fillUserSelect(users);
                                        userSelect.removeAttribute('disabled');
                                        confirmBtn.removeAttribute('disabled');
                                        usersLoaded = true;
                                    })
                                    .catch(function() {
                                        userSelect.innerHTML = '<option value="">Erro ao carregar usuários</option>';
                                        emptyState.textContent = 'Não foi possível carregar a lista de usuários.';
                                        emptyState.classList.remove('d-none');
                                    });
                            };

                            $('#addTaskAssigneeModal').on('show.bs.modal', loadSelectableUsers);
                        });
                    </script>
                @endcan

            </div>
        </div>

        {{-- Inclusão dos Modais --}}
        @include('tasks.edit', ['task' => $task])

        {{-- Reabre o modal caso haja erro de validação na edição (Vanilla JS) --}}
        @if ($errors->any() && old('_method') === 'PUT')
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const editBtn = document.querySelector('[data-target="#modalEditarTask"]');
                    if (editBtn) editBtn.click();
                });
            </script>
        @endif


    </div>
@endsection
