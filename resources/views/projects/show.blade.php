@extends('layouts.app')

@section('title', 'Detalhes do Projeto')

@section('content')
  @php
    $tasksEnabled = $project->isModuleEnabled('tasks');
    $activeResolvedModules = collect($resolvedModules ?? [])
        ->filter(fn($module) => (bool) ($module['enabled'] ?? false))
        ->values()
        ->all();
  @endphp

  <div class="card">
    @include('projects.partials.show.show-header')
    <div class="card-body">
      <div class="row">
        <div class="col-md-8">
          @include('projects.partials.show.show-card-descricao')
          @include('comments.partials.thread', ['commentable' => $project, 'commentableType' => 'project'])
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
    </div>
  </div>
  @if ($tasksEnabled)
    @include('tasks.partials.components.task-form-modal')
  @endif

@endsection
