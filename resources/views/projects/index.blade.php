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
        <div class="col-12 col-lg-6 col-xl-4 project-item mb-2 px-2"
          data-searchable="{{ strtolower($project->name . ' ' . ($project->description ?? '') . ' ' . ($project->tags->pluck('name')->implode(' ') ?? '')) }}">
          <x-project-card :project="$project" />
        </div>
      @endforeach
    </div>
  </section>

@endsection
