@extends('layouts.app')

@section('title', $title . ' | Configurações do Projeto')
@section('content')
  <div class="card">
    @include('projects.partials.show.show-header')
    <div class="card-body">
      <div class="row">
        <div class="col-12 col-lg-6 mb-4">
          @include('projects.partials.show.settings-card')
          @include('projects.partials.show.settings-danger-area')
        </div>

        <div class="col-12 col-lg-6">
          {{-- <div class="mb-4">
            @include('projects.partials.show.project-type-card')
          </div> --}}

          @include('projects.partials.show.watch-settings-card')

          <div class="mb-4">
            @include('projects.partials.show.show-card-membros')
          </div>

          <div class="mt-4">
            @include('projects.partials.show.show-card-modulos', ['showToggle' => true])
          </div>

        </div>
      </div>
    </div>
  </div>
@endsection
