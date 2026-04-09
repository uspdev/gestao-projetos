@extends('layouts.app')

@section('title', 'Detalhes do Projeto')

@section('content')
    <div class="container-fluid">
        {{-- Navegação / Breadcrumb Simplificado --}}
        <div class="mb-3">
            <a href="{{ route('users.projects.index', auth()->id()) }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> Voltar para Projetos
            </a>
        </div>

        {{-- Form de Edição de Projeto --}}
        <div class="collapse mb-4" id="collapseEditarProjeto">
            <div class="card card-body border-primary">
                @include('projects.edit', ['project' => $project])
            </div>
        </div>

        <div class="row">
            {{-- COLUNA PRINCIPAL: Título, Descrição e Tasks --}}
            <div class="col-md-8">

                {{-- Card: Título e Descrição --}}
                <div class="card mb-4 shadow-sm">
                    <div class="card-body">
                        {{-- Título --}}
                        <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-4">
                            <h2 class="m-0 text-dark font-weight-bold">{{ $project->name }}</h2>
                            <div>
                                <button class="btn btn-outline-primary btn-sm" data-toggle="collapse"
                                    data-target="#collapseEditarProjeto">
                                    <i class="fas fa-edit"></i> Editar
                                </button>
                            </div>
                        </div>

                        {{-- Descrição --}}
                        <div class="text-dark text-justify" style="font-size: 1.1rem; line-height: 1.6;">
                            @if ($project->description)
                                {!! nl2br(e($project->description)) !!}
                            @else
                                <div class="text-center text-muted p-5 bg-light rounded">
                                    <i class="fas fa-align-left fa-3x mb-3 text-secondary"></i>
                                    <h5>Sem descrição</h5>
                                    <p class="mb-0">Nenhuma descrição foi fornecida para este projeto.</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Sessão: Tarefas do Projeto --}}
                <div class="mt-5 mb-3 d-flex justify-content-between align-items-center">
                    <h4 class="m-0 text-dark font-weight-bold">Tarefas do Projeto</h4>
                    <button class="btn btn-sm btn-success" data-toggle="collapse" data-target="#collapseNovaTask">
                        <i class="fas fa-plus"></i> Nova Task
                    </button>
                </div>

                {{-- Form: Nova Task --}}
                <div class="collapse mb-4" id="collapseNovaTask">
                    <div class="card card-body bg-light border-success">
                        @include('project-tasks.create', ['project_id' => $project->id])
                    </div>
                </div>

                {{-- Index: Lista de Tasks --}}
                <div class="card card-body mb-4 shadow-sm">
                    @include('project-tasks.index', ['tasks' => $project->tasks])
                </div>

            </div>

            {{-- COLUNA LATERAL (Direita): Informações e Membros --}}
            <div class="col-md-4">

                {{-- Informações --}}
                <div class="card mb-4 shadow-sm border-top-primary">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="text-muted font-weight-bold">Status atual</span>
                            <span class="badge {{ $project->status->color() }} p-2" style="font-size: 1rem;">
                                {{ $project->status->label() }}
                            </span>
                        </div>
                    </div>
                </div>

                {{-- Membros do Projeto --}}
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h6 class="m-0 text-muted">
                            <i class="fas fa-users mr-1"></i> Membros do Projeto
                        </h6>
                        <button class="btn btn-sm btn-outline-success" title="Adicionar membro" data-toggle="modal"
                            data-target="#addProjectMemberModal">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                    <ul class="list-group list-group-flush">
                        @forelse($project->users as $user)
                            @include('users.preview', ['user' => $user])
                        @empty
                            <li class="list-group-item text-muted font-italic small text-center py-3">
                                Nenhum membro vinculado.
                            </li>
                        @endforelse
                    </ul>
                </div>

                <div class="modal fade" id="addProjectMemberModal" tabindex="-1" role="dialog"
                    aria-labelledby="addProjectMemberModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered" role="document">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="addProjectMemberModalLabel">Adicionar membro ao projeto</h5>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <form method="POST" action="{{ route('projects.members.store', $project) }}">
                                @csrf
                                <div class="modal-body">
                                    <div id="project-member-empty-state"
                                        class="alert alert-light border text-muted mb-3 d-none"></div>

                                    <div class="form-group">
                                        <label for="member-user-id" class="font-weight-bold">Usuário</label>
                                        <select id="member-user-id" name="user_id"
                                            class="form-control @error('user_id') is-invalid @enderror" disabled>
                                            <option value="">Carregando usuários...</option>
                                        </select>
                                        @error('user_id')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>

                                    <div class="form-group mb-0">
                                        <label for="member-role" class="font-weight-bold">Role no projeto</label>
                                        <select id="member-role" name="role"
                                            class="form-control @error('role') is-invalid @enderror">
                                            <option value="">Selecione...</option>
                                            @foreach (\App\Enums\Project\ProjectUserRole::cases() as $role)
                                                <option value="{{ $role->value }}" @selected(old('role') === $role->value)>
                                                    {{ ucfirst(strtolower($role->value)) }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('role')
                                            <div class="invalid-feedback d-block">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-outline-secondary"
                                        data-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-primary" id="project-member-confirm-btn" disabled>
                                        Confirmar
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                @if ($errors->has('user_id') || $errors->has('role'))
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            $('#addProjectMemberModal').modal('show');
                        });
                    </script>
                @endif

                <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const modal = document.getElementById('addProjectMemberModal');
                        const userSelect = document.getElementById('member-user-id');
                        const confirmBtn = document.getElementById('project-member-confirm-btn');
                        const emptyState = document.getElementById('project-member-empty-state');
                        const loadUrl = '{{ route('projects.members.selectable', $project) }}';
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
                                        userSelect.innerHTML = '<option value="">Nenhum usuário disponível</option>';
                                        emptyState.textContent =
                                            'Não há usuários disponíveis para adicionar a este projeto.';
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

                        $('#addProjectMemberModal').on('show.bs.modal', loadSelectableUsers);
                    });
                </script>

            </div>
        </div>
    </div>
@endsection
