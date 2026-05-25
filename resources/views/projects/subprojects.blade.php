@extends('layouts.project')

@section('title', 'Detalhes do Projeto')

@section('project-content')
  <div class="row">
    <div class="col-md-8">
      @include('projects.partials.show.show-card-subprojects')
    </div>
    <div class="col-md-4">
      @include('projects.partials.show.show-card-descricao')
      {{-- @include('projects.partials.show.show-card-meetings') --}}
    </div>

  @endsection
