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

                {{-- Card: Título e Descrição --}}
                <div class="card mb-4 shadow-sm">
                    <div class="card-body">
                        {{-- Título --}}
                        <div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-4">
                            <h2 class="m-0 text-dark font-weight-bold">{{ $project->name }}</h2>
                            <div>
                                @can('update', $project)
                                    <button type="button" class="btn btn-outline-primary btn-sm" data-toggle="modal" data-target="#modalEditarProjeto">
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                @endcan
                                @can('delete', $project)
                                    <form method="POST" action="{{ route('projects.destroy', $project) }}" class="d-inline-block ml-1"
                                        onsubmit="return confirm('Deseja realmente excluir este projeto? Esta ação não pode ser desfeita.');">
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

            </div>

            {{-- COLUNA LATERAL (Direita): Informações, Membros e Tasks --}}
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
                        @can('storeMember', $project)
                            <button class="btn btn-sm btn-outline-success" title="Adicionar membro" data-toggle="modal"
                                data-target="#addProjectMemberModal">
                                <i class="fas fa-plus"></i>
                            </button>
                        @endcan
                    </div>
                    <ul class="list-group list-group-flush">
                        @forelse($project->users as $user)
                            @include('users.preview', [
                                'user' => $user,
                                'project' => $project,
                                'canManageMembers' => auth()->user()->can('storeMember', $project),
                            ])
                        @empty
                            <li class="list-group-item text-muted font-italic small text-center py-3">
                                Nenhum membro vinculado.
                            </li>
                        @endforelse
                    </ul>
                </div>

                {{-- Index: Lista de Tasks --}}
                <div class="card mb-4 shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <h6 class="m-0 text-muted">
                            <i class="fas fa-tasks mr-1"></i> Tasks
                        </h6>
                        @can('create', [\App\Models\Task::class, $project])
                            <button type="button" class="btn btn-sm btn-outline-success" data-toggle="modal" data-target="#modalNovaTask" title="Adicionar task">
                                <i class="fas fa-plus"></i>
                            </button>
                        @endcan
                    </div>
                    <div class="card-body">
                        @include('project-tasks.index', [
                            'tasks' => $project->tasks,
                            'taskCardColumnClass' => 'col-12',
                        ])
                    </div>
                </div>
            </div>
        </div>
    </div>

    @can('update', $project)
        @include('projects.edit', ['project' => $project])
    @endcan

    @can('storeMember', $project)
        @include('partials.projects.add-member', ['project' => $project])
    @endcan
    
    @can('create', [\App\Models\Task::class, $project])
        @include('project-tasks.create', ['project_id' => $project->id])
    @endcan

    {{-- Reabre o modal caso haja erro de validação na edição --}}
    @can('update', $project)
        @if ($errors->any() && old('_method') === 'PUT')
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const editBtn = document.querySelector('[data-target="#modalEditarProjeto"]');
                    if (editBtn) editBtn.click();
                });
            </script>
        @endif
    @endcan

</div>

@endsection