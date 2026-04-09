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
                        <button class="btn btn-sm btn-outline-success" title="Adicionar membro">
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

            </div>
        </div>
    </div>
@endsection
