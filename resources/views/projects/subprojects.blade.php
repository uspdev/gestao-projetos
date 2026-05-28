@extends('layouts.project')

@section('title', 'Detalhes do Projeto')

@section('project-content')
  <div class="row">
    <div class="col-md-8">
      @include('projects.partials.show.show-card-subprojects')
    </div>
    <div class="col-md-4">
      <div class="mb-4">
        @include('projects.partials.show.project-type-card')
      </div>
      @include('projects.partials.show.show-card-modulos', [
          'resolvedModules' => $project->activeModulesSummary(),
      ])
      @include('projects.partials.show.show-card-descricao')
    </div>
  </div>

@endsection
