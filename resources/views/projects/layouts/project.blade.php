@extends('layouts.app')

@section('title', $title . ' | Detalhes do Projeto')

@section('content')
  <div id="{{ deep_link_fragment($project) }}" class="card" tabindex="-1" data-deep-link-target>
    @include('projects.partials.show.show-header')
    <div class="card-body p-2">
      @yield('project-content')
    </div>
  </div>
@endsection
