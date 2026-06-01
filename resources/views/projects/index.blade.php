@extends('layouts.app')

@section('title', 'Projetos')

@section('content')

  <div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-2">
      <h2 class="mb-0">Projetos</h2>
      @include('projects.partials.components.search-project-form')
    </div>
  </div>

  @include('projects.partials.index.pinned-projects-section')
  @include('projects.partials.index.all-projects-section')

@endsection
