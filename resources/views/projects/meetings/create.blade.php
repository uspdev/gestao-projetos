@extends('layouts.app')

@section('title', $project->name . ' - Nova reuniao')

@section('content')
  <div class="card">
    @include('projects.partials.show.show-header')
    <div class="card-body">
      <div class="row">
        <div class="col-lg-1"></div>
        <div class="col mb-4 mb-lg-0">
          <div class="card shadow-sm">
            <div class="card-header h5">
              <i class="far fa-calendar-plus mr-1"></i> Nova reuniao
            </div>
            <div class="card-body">
              @include('projects.meetings.partials.form', [
                  'action' => route('projects.meetings.store', $project),
                  'method' => 'POST',
                  'meeting' => null,
              ])
            </div>
          </div>
        </div>
        <div class="col-lg-1"></div>
      </div>
    </div>
  </div>
@endsection
