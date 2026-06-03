@extends('layouts.app')

@section('title', $title . ' | Projetos')

@section('content')

  <section id="todos-projetos">
    <div class="d-flex justify-content-left align-items-center mb-3">
      <h4 class="mt-2">
        Todos os projetos
        <span id="projects-count" class="badge badge-pill badge-primary">{{ $projects->count() }}</span>
      </h4>
      @include('projects.partials.components.search-project-form')
    </div>

    <div class="row" id="projects-list">
      @foreach ($projects as $project)
        <div class="col-md-4 project-item"
          data-searchable="{{ strtolower($project->name . ' ' . ($project->description ?? '') . ' ' . ($project->tags->pluck('name')->implode(' ') ?? '')) }}">
          @include('projects.partials.components.preview')
        </div>
      @endforeach
    </div>
  </section>

@endsection
