@extends('layouts.app')

@section('title', 'Tarefas do Projeto')

@section('content')
  <div class="card">
    <div class="card-header h4 d-flex justify-content-between align-items-center gap-2">
      <div class="">
        <a href="{{ route('projects.index') }}" class="text-decoration-none text-secondary">
          <i class="fas fa-home"></i>
        </a>
        <i class="fas fa-angle-right text-muted"></i>
        <a href="{{ route('projects.show', $project) }}" class="text-decoration-none text-secondary">
          <span>{{ $project->name }}</span>
        </a>
        <i class="fas fa-angle-right text-muted"></i> <span>Tarefas</span>
      </div>
    </div>
    <div class="card-body">
      <div class="row">
        <div class="col-md-8">
          @include('projects.partials.show-card-tasks-table')
        </div>
        <div class="col-md-4">
          @include('projects.partials.show-card-membros')
          @include('projects.partials.show-card-descricao')
        </div>
      </div>
    </div>
  </div>
@endsection
