@extends('layouts.app')

@section('title', $title . ' | Detalhes do Projeto')

@section('content')
  <div class="card app-card app-card--blue">
    @include('projects.partials.show.show-header')
    <div class="card-body p-2">
      @yield('project-content')
    </div>
  </div>
@endsection
