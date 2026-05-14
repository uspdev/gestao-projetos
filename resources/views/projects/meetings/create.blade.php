@extends('layouts.app')

@section('title', $project->name . ' - Nova reuniao')

@section('content')
  <div class="card">
    @include('projects.partials.show.show-header')
    <div class="card-body">
      <div class="card mb-4 shadow-sm">
        <div class="card-header h5">
          <i class="far fa-calendar-plus mr-1"></i> Nova reuniao
        </div>
        <div class="card-body">
          @include('projects.meetings.partials.form', [
              'action' => route('projects.meetings.store', $project),
              'method' => 'POST',
              'meeting' => null,
              'availableProjects' => $availableProjects,
              'selectedProjects' => $selectedProjects,
          ])
        </div>
      </div>
    </div>
  </div>
@endsection
