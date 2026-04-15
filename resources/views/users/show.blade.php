@extends('layouts.app')

@section('title', 'Perfil do Usuário')

@section('content')
<div class="container-fluid">
    {{-- Card de Informações do Usuário --}}
    <div class="card mb-4 shadow-sm border-top-primary">
        <div class="card-body d-flex align-items-center">
            {{-- Avatar --}}
            <div class="mr-4 text-secondary">
                <i class="fas fa-user-circle fa-5x"></i>
            </div>
            
            <div>
                <h3 class="m-0 font-weight-bold text-dark">{{ $user->name }}</h3>
                <p class="text-muted mb-2 fs-5">
                    <i class="fas fa-envelope mr-1"></i> {{ $user->email }}
                </p>
                
                <div class="d-flex align-items-center mt-2">
                    {{-- Número USP --}}
                    @if($user->codpes)
                        <span class="badge badge-info p-2 mr-2">
                            <i class="fas fa-id-card mr-1"></i> Nº USP: {{ $user->codpes }}
                        </span>
                    @endif
                    
                    {{-- Roles --}}
                    @if($user->roles && $user->roles->count() > 0)
                        @foreach($user->roles as $role)
                            <span class="badge badge-dark p-2 mr-1">{{ ucfirst($role->name) }}</span>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>
    </div>

    <hr class="my-4">

    {{-- Seção de Projetos Atribuídos --}}
    <div class="mb-5">
        <h4 class="mb-3 text-secondary">
            <i class="fas fa-project-diagram mr-2"></i> Projetos Atribuídos
        </h4>
        
        <div class="row">
            {{-- Assume que a relação belongsToMany 'projects' está definida na Model User --}}
            @forelse($user->projects as $project)
                <div class="col-md-6 col-lg-4 mb-3">
                    @include('partials.projects.preview', ['project' => $project])
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-light border text-muted">
                        Este usuário não está alocado em nenhum projeto no momento.
                    </div>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Seção de Tarefas Atribuídas --}}
    <div>
        <h4 class="mb-3 text-secondary">
            <i class="fas fa-tasks mr-2"></i> Tarefas
        </h4>
        
        <div class="row">
            {{-- Assume que a relação belongsToMany 'tasks' está definida na Model User --}}
            @forelse($user->tasks as $task)
                <div class="col-md-6 col-lg-4 mb-3">
                    @include('partials.tasks.preview', ['task' => $task])
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-light border text-muted">
                        Este usuário não possui tarefas pendentes atribuídas a ele.
                    </div>
                </div>
            @endforelse
        </div>
    </div>

</div>
@endsection