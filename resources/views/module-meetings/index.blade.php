@extends('module-meetings.layouts.meeting')

@section('title', $title . ' | ' . $project->name)

@section('meeting-header')
  @include('module-meetings.partials.create-btn')
  @include('module-meetings.partials.buttons.show-completed-btn')
@endsection

@section('meeting-content')
  @if ($meetings->isEmpty())
    <div class="alert alert-light border text-muted mb-0">Nenhuma reuniao cadastrada.</div>
  @else
    <div id="meetings-list" class="meetings-list" style="max-height: min(70vh, 42rem); overflow-y: auto; overflow-x: hidden; overscroll-behavior-y: auto;"
      data-deep-link-target
      tabindex="0" aria-label="Lista de reuniões">
      <div class="row">
        @foreach ($meetings as $meeting)
          <div class="col-md-6 mb-4">
            @include('module-meetings.partials.meeting-card')
          </div>
        @endforeach
      </div>
    </div>
  @endif

@endsection
