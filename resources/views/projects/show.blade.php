@extends('layouts.app')

@section('title', 'Detalhes do Projeto')

@section('content')
    <div class="container-fluid">
        {{-- Full view de Project --}}
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="m-0">{{ $project->name }}</h4>
                <div>
                    <button class="btn btn-sm btn-outline-primary" data-toggle="collapse" data-target="#collapseEditarProjeto">
                        <i class="fas fa-edit"></i> Editar Projeto
                    </button>
                </div>
            </div>
            <div class="card-body">
                <p>
                    <strong>Status:</strong>
                    <span class="badge {{ $project->status->color() }}">
                        {{ $project->status->label() }}
                    </span>
                </p>
                <p><strong>Descrição:</strong></p>
                <p class="text-justify">{{ $project->description ?: 'Sem descrição informada.' }}</p>
            </div>
        </div>

        {{-- Form de Edição de Project --}}
        <div class="collapse mb-4" id="collapseEditarProjeto">
            <div class="card card-body border-primary">
                @include('projects.edit', ['project' => $project])
            </div>
        </div>

        <hr>

        {{-- Tasks --}}
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4>Tarefas do Projeto</h4>
            <div>
                {{-- Exibe/Oculta Tasks --}}
                <button class="btn btn-secondary" data-toggle="collapse" data-target="#collapseTasksIndex">
                    <i class="fas fa-list"></i> Exibir Tasks
                </button>
                {{-- Form de nova Task --}}
                <button class="btn btn-success" data-toggle="collapse" data-target="#collapseNovaTask">
                    <i class="fas fa-plus"></i> Nova Task
                </button>
            </div>
        </div>

        {{-- Form de Criação de Task  --}}
        <div class="collapse mb-4" id="collapseNovaTask">
            <div class="card card-body bg-light">
                @include('project-tasks.create', ['project_id' => $project->id])
            </div>
        </div>

        {{-- Index das Tasks do Projeto --}}
        <div class="collapse show" id="collapseTasksIndex">
            <div class="card card-body">
                @include('project-tasks.index', ['tasks' => $project->tasks])
            </div>
        </div>

    </div>
@endsection
