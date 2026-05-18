@extends('layouts.app')

@section('title', 'Meus Projetos')

@section('content')
  @php
    $search = trim((string) request()->input('search', ''));
    $allProjects = $projects;
  @endphp

  <div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <div class="d-flex align-items-center">
        <h2 class="mb-0">Meus Projetos</h2>
        @include('projects.partials.components.search-project-form')
      </div>
    </div>

    <section id="projetos-pinnados" class="mb-5">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h4 class="mb-1">Projetos pinados</h4>
        </div>
      </div>

      <div class="row">
        @forelse($pinnedProjects as $project)
          <div class="col-md-4">
            @include('projects.partials.components.preview')
          </div>
        @empty
          <div class="col-12">
            <div class="alert alert-light border mb-0">
              Você ainda não fixou nenhum projeto.
            </div>
          </div>
        @endforelse
      </div>
    </section>

    <section id="todos-projetos">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
          <h4 class="mb-1">Todos os projetos</h4>
          <p class="text-muted mb-0">{{ $allProjects->count() }} projeto(s) encontrado(s).</p>
        </div>
      </div>

      <div class="row">
        @forelse($allProjects as $project)
          <div class="col-md-4">
            @include('projects.partials.components.preview')
          </div>
        @empty
          <div class="col-12">
            <div class="alert alert-info">
              @if ($search !== '')
                Nenhum projeto encontrado para "{{ $search }}".
              @else
                Você ainda não possui projetos em andamento.
              @endif
            </div>
          </div>
        @endforelse
      </div>
    </section>
  </div>

@endsection
