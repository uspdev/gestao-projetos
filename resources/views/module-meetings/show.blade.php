@extends('module-meetings.layouts.meeting')

@section('title', $title . ' | ' . $project->name)

@section('meeting-header')
  <x-separator /> <b>{{ $meeting->title }}</b>
@endsection

@section('meeting-content')
  <div class="row">
    <div class="col-lg-8 mb-4 mb-lg-0">
      @include('module-meetings.partials.meeting-notes')
      @include('module-meetings.partials.meeting-actions')
      @include('module-meetings.partials.meeting-records')
      @include('comments.partials.thread', ['commentable' => $meeting])
    </div>

    <div class="col-lg-4">
      @include('module-meetings.partials.overview')
    </div>
  </div>
@endsection
