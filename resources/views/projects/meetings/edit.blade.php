@extends('layouts.app')

@section('title', $project->name . ' - Editar reuniao')

@section('content')
  <div class="card">
    @include('projects.partials.show.show-header')
    <div class="card-body">
      <div class="card mb-4 shadow-sm">
        <div class="card-header h5">
          <i class="far fa-edit mr-1"></i> Editar reuniao
        </div>
        <div class="card-body">
          @include('projects.meetings.partials.form', [
              'action' => route('projects.meetings.update', [$project, $meeting]),
              'method' => 'PUT',
              'meeting' => $meeting,
              'availableProjects' => $availableProjects,
              'selectedProjects' => $selectedProjects,
          ])
        </div>
      </div>

        @include('projects.meetings.partials.items-list', [
          'meetingItems' => $meetingItems,
          'meeting' => $meeting,
          'project' => $project,
        ])
      @include('projects.meetings.partials.items-form', [
          'project' => $project,
          'meeting' => $meeting,
          'meetingItems' => $meetingItems,
          'meetingProjects' => $meetingProjects,
      ])

      @include('comments.partials.thread', ['commentable' => $meeting])
    </div>
  </div>
@endsection
