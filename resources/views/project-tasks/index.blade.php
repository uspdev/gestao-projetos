@extends('layouts.app')

@section('title', 'Tarefas do Projeto')

@section('content')
  <div class="card">
    <div class="card-header h4 d-flex justify-content-between align-items-center gap-2">
      <div class="">
        <a href="{{ route('users.projects.index', auth()->id()) }}" class="text-decoration-none text-secondary">
          Meus Projetos
        </a>
        <i class="fas fa-angle-right text-muted"></i>
        <a href="{{ route('projects.show', $project) }}" class="text-decoration-none text-secondary">
          <span>{{ $project->name }}</span>
        </a>

        <i class="fas fa-angle-right text-muted"></i> <span>Tarefas</span>

        {{-- <a href="{{ route('projects.tasks.index', $project) }}" class="btn btn-sm btn-outline-secondary ms-3">Tarefas</a> --}}
      </div>

      <!-- Lado direito -->
      <div class="d-flex align-items-center gap-2">
        @include('projects.partials.update-status')
        @include('projects.partials.edit-btn')
        @include('projects.partials.delete-btn')
      </div>

    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-8">
          @include('projects.partials.show-card-tasks')
        </div>
        <div class="col-md-4">
          @include('projects.partials.show-card-membros')
          @include('projects.partials.show-card-descricao')
        </div>
      </div>

    @include('project-tasks.partials.list')

    <div class="col-md-4">
      @include('projects.partials.show-card-membros')
      @include('projects.partials.show-card-descricao')
    </div>
  </div>

  </div>
  </div>












  <div class="row">

  </div>
@endsection
