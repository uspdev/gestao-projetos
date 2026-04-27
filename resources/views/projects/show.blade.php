@extends('layouts.app')

@section('title', 'Detalhes do Projeto')
@section('content')
  {{-- Card: Título e Descrição --}}
  <div class="card">
    <div class="card-header h4 d-flex justify-content-between align-items-center gap-2">
      <div class="">
        <a href="{{ route('projects.index') }}" class="text-decoration-none text-secondary">
          Meus Projetos
        </a>
        <i class="fas fa-angle-right text-muted"></i> <span>{{ $project->name }}</span>

       
      </div>

      <div class="d-flex align-items-center gap-2">
        @include('projects.partials.update-status')
        @include('projects.partials.edit-btn')
        @include('projects.partials.delete-btn')
      </div>

    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-8">
          @include('projects.partials.show-card-descricao')
        </div>
        <div class="col-md-4">
          @include('projects.partials.show-card-membros')
          @include('projects.partials.show-card-tasks')
        </div>
      </div>

    </div>
  </div>

  {{-- todo: alert em geral deve usar do theme. --}}
  @if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      {{ session('error') }}
      <button type="button" class="close" data-dismiss="alert" aria-label="Fechar">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
  @endif
{{-- @dd($project) --}}

@endsection
