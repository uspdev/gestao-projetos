@extends('layouts.project-meeting')

@section('title', $project->name . ' - Reuniao')

@section('meeting-header')
  <x-separator /> <b>{{ $meeting->title }}</b>
  @include('module-meetings.partials.status-badge')

  @if ($showActions = true)
    @include('module-meetings.partials.edit-btn')
    @include('module-meetings.partials.delete-btn')
  @endif
@endsection

@section('meeting-content')
  <div class="row">
    <div class="col-lg-8 mb-4 mb-lg-0">
      @include('module-meetings.partials.overview')
      @include('comments.partials.thread', ['commentable' => $meeting])
    </div>

    <div class="col-lg-4">
      @include('module-meetings.partials.items-list')
    </div>
  </div>
@endsection
