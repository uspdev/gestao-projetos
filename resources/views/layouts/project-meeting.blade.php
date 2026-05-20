@extends('layouts.project')

@section('title', $project->name . ' - Reunioes')

@section('project-content')
  <div class="card shadow-sm">
    <div class="card-header h5 py-2" style="background-color: lightCyan;">
      <span><i class="far fa-calendar-alt"></i> Reuniões</span>
      <span class="badge badge-pill badge-secondary">{{ $project->meetings->count() }}</span>
      @include('module-meetings.partials.create-btn')

      @yield('meeting-header')
    </div>

    <div class="card-body p-2">
      @yield('meeting-content')
    </div>
  </div>

@endsection
