@extends('layouts.app')

@section('title', $project->name . ' - Editar reuniao')

@section('content')
  <div class="card">
    @include('projects.partials.show.show-header')
    <div class="card-body">
      <div class="row">
        <div class="col-lg-8 mb-4 mb-lg-0">
          <div class="card shadow-sm mb-4">
            <div class="card-header h5">
              <i class="far fa-edit mr-1"></i> Editar reuniao
            </div>
            <div class="card-body">
              @include('projects.meetings.partials.form', [
                  'action' => route('projects.meetings.update', [$project, $meeting]),
                  'method' => 'PUT',
              ])
            </div>
          </div>

          @include('comments.partials.thread', ['commentable' => $meeting])
        </div>

        <div class="col-lg-4">
          @include('projects.meetings.partials.overview-card', [
              'showActions' => false,
          ])
          @include('projects.meetings.partials.items-form')
          @include('projects.meetings.partials.items-list')
        </div>
      </div>
    </div>
  </div>
@endsection
