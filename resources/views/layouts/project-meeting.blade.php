@extends('layouts.project')

@section('title', $project->name . ' - Reunioes')

@section('project-content')
  <div class="card shadow-sm">
    <div class="card-header h5 py-2" style="background-color: lightCyan;">
      <a href="{{ route('projects.meetings.index', $project) }}" class="text-decoration-none text-dark">
        <i class="far fa-calendar-alt"></i> Reuniões
      </a>
      <span class="badge badge-pill badge-secondary">{{ $project->meetings->count() }}</span>

      @yield('meeting-header')
      @include('module-meetings.partials.create-btn')
    </div>

    <div class="card-body p-2">
      @yield('meeting-content')
    </div>
  </div>

@endsection
