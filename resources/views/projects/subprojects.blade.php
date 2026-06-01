@extends('layouts.project')

@section('title', 'Detalhes do Projeto')

@section('project-content')
  @include('projects.partials.show.join-inherited-banner')

  <div class="row">
    <div class="col-md-8">
      <x-projects::show.subprojects-card :project="$project" type="main" />
    </div>
    <div class="col-md-4">
      <x-projects::show.tipo-card :project="$project" />
      @include('projects.partials.show.show-card-modulos')
      <x-projects::show.descricao-card :project="$project" />
      <x-projects::show.membros-preview-card :project="$project" />
    </div>
  </div>
@endsection
