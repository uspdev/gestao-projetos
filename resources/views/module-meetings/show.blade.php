@extends('module-meetings.layouts.meeting')

@section('title', $title . ' | ' . $project->name)

@section('meeting-header')
  <div class="float-right d-flex align-items-center">
    <div class="mr-2 mb-1">
      @include('module-meetings.partials.duplicate-btn')
    </div>

    <a href="{{ route('projects.meetings.export', [$project, $meeting]) }}" class="btn btn-sm btn-outline-secondary py-0">
      <i class="fas fa-download"></i> Exportar
    </a>
  </div>

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
