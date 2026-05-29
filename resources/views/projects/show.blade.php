@extends('layouts.project')

@section('title', 'Detalhes do Projeto')

@section('project-content')
  <div class="row">
    <div class="col-md-8">
      <x-projects::show.descricao-card :project="$project" type="main" />
      @include('comments.partials.thread', ['commentable' => $project])
    </div>
    <div class="col-md-4">
      <x-projects::show.tipo-card :project="$project" />
      @include('projects.partials.show.show-card-modulos', [
          'resolvedModules' => $project->activeModulesSummary(),
      ])
      <x-projects::show.subprojects-card :project="$project" />
      <x-projects::show.membros-preview-card :project="$project" />
    </div>
  </div>
@endsection
