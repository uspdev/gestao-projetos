@extends('layouts.app')

@section('title', 'Detalhes do Projeto')
@section('content')
  {{-- Card: Título e Descrição --}}
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center gap-2">
      <div class="h4 mb-0">
        <a href="{{ route('projects.index') }}" class="text-decoration-none text-secondary">
          <i class="fas fa-home"></i>
        </a>
        <x-separator /> {{ $project->name }}

      </div>

      <div class="d-flex align-items-center gap-2">
        @include('projects.partials.show-tag-badges')
        @include('projects.partials.edit-btn')
        @include('projects.partials.update-status')
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
  @include('tasks.partials.task-form-modal')
@endsection
