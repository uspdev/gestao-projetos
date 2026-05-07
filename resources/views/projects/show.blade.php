@extends('layouts.app')

@section('title', 'Detalhes do Projeto')

@section('content')
  <div class="card">
    @include('projects.partials.show.show-header')
    <div class="card-body">
      <div class="row">
          <div class="col-md-8">
          @include('projects.partials.show.show-card-descricao')
        </div>
        <div class="col-md-4">
          @include('projects.partials.show.show-card-membros')
          @include('projects.partials.show.show-card-modulos')
        </div>
      </div>
    </div>
  </div>
  @include('tasks.partials.task-form-modal')
@endsection
