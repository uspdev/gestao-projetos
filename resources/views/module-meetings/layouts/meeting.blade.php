@extends('projects.layouts.project')


@section('title', $title . ' | ' . $project->name)
@php
  // para funcionar em views que não tem o toggle de show completed, como a show.blade.php,
  // assim continua levando em consideração a escolha do usuário
  $showCompleted = $showCompleted ?? request()->boolean('show_completed');
  $meetingsCount = $project->meetingsCount($showCompleted);
@endphp

@section('project-content')
  <div class="card shadow-sm">
    <div class="card-header h5 py-2" style="background-color: lightCyan;">
      <a href="{{ route('projects.meetings.index', $project) }}" class="text-decoration-none text-dark">
        <i class="far fa-calendar-alt"></i> Reuniões
      </a>
      <span class="badge badge-pill badge-secondary">{{ $meetingsCount }}</span>

    @section('meeting-header')
      @include('module-meetings.partials.create-btn')
    @show
  </div>
  <div class="card-body p-2">
    @yield('meeting-content')
  </div>
</div>
@endsection
