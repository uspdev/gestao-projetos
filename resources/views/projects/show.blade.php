@extends('layouts.project')

@section('title', 'Detalhes do Projeto')

@section('project-content')
  <div class="row">
    <div class="col-md-8">
      @include('projects.partials.show.show-card-descricao')
      @include('comments.partials.thread', ['commentable' => $project])
    </div>
    <div class="col-md-4">
      <div class="mb-4">
        @include('projects.partials.show.project-type-card')
      </div>
      @include('projects.partials.show.show-card-modulos', [
          'resolvedModules' => $project->activeModulesSummary(),
      ])
      @if (!$project->isSubproject() && $project->projectType?->slug === 'organizacional')
        @include('projects.partials.show.show-card-subprojects')
      @endif

    </div>
  </div>
@endsection
