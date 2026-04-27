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
          @forelse($tasks as $task)
            @include('partials.tasks.preview')
          @empty
            <div class="col-12">
              <div class="alert alert-secondary text-center p-4 shadow-sm" role="alert">
                <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                <h5 class="text-muted m-0">Nenhuma tarefa encontrada.</h5>
                <p class="text-muted mb-0 mt-2">
                  Clique em <strong>"Nova Task"</strong> acima para criar a primeira tarefa deste projeto.
                </p>
              </div>
            </div>
          @endforelse
        </div>
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
