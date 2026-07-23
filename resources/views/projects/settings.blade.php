@extends('layouts.app')

@section('title', $title . ' | Configurações do Projeto')
@section('content')
  <div class="card">
    @include('projects.partials.show.show-header')
    <div class="card-body">
      <div class="row">
        <div class="col-12 col-lg-6 mb-4">
          @include('projects.partials.show.settings-card')
          <div class="mt-4">
            @include('projects.partials.show.show-card-modulos', ['showToggle' => true])
          </div>
        </div>

        <div class="col-12 col-lg-6">
          {{-- <div class="mb-4">
            @include('projects.partials.show.project-type-card')
          </div> --}}

          <div class="mb-4">
            @include('projects.partials.show.show-card-membros')
          </div>

          @if ($project->isOrganizational() || $project->isSubproject())
            <div class="mb-4">
              @include('projects.partials.show.subproject-permissions-card')
            </div>
          @endif

          <div class="card border-danger">
            <div class="card-header h6 py-2 text-danger font-weight-bold">Área de risco</div>
            <div class="card-body">
              <p class="text-muted mb-3">
                A remoção do projeto é permanente.
                Mexa aqui somente se souber exatamente o que está fazendo!
              </p>
              @include('projects.partials.buttons.delete-btn')
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection
