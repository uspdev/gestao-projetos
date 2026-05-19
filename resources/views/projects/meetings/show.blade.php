@extends('layouts.app')

@section('title', $project->name . ' - Reuniao')

@section('content')
  <div class="card">
    @include('projects.partials.show.show-header')
    <div class="card-body">
      <div class="row">
        <div class="col-lg-8 mb-4 mb-lg-0">
          @include('projects.meetings.partials.overview-card', [
              'showActions' => true,
          ])

          @include('comments.partials.thread', ['commentable' => $meeting])
        </div>

        <div class="col-lg-4">
          @include('projects.meetings.partials.items-form')
          @include('projects.meetings.partials.items-list')
        </div>
      </div>
    </div>
  </div>
@endsection
