@extends('layouts.project')

@section('title', 'Detalhes do Projeto')

@section('project-content')
  @php
    $tasksEnabled = $project->isModuleEnabled('tasks');
    $activeResolvedModules = collect($resolvedModules ?? [])
        ->filter(fn($module) => (bool) ($module['enabled'] ?? false))
        ->values()
        ->all();
  @endphp
  <div class="row">
    <div class="col-md-8">
      @include('projects.partials.show.show-card-descricao')
      @include('comments.partials.thread', ['commentable' => $project])
    </div>
    <div class="col-md-4">
      @if (!$project->isSubproject() && $project->projectType?->slug === 'organizacional')
        @include('projects.partials.show.show-card-subprojects')
      @endif
      @include('projects.partials.show.show-card-modulos', [
          'resolvedModules' => $activeResolvedModules,
      ])
    </div>
  </div>
@endsection
