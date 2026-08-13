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

  <x-separator />
  <span class="d-inline-flex align-items-center flex-wrap" style="gap: 0.5rem;">
    <b>{{ $meeting->title }}</b>
    @include('module-meetings.partials.status-badge')
  </span>
@endsection

@section('meeting-content')
  <div data-file-reference-context-type="meeting" data-file-reference-context-id="{{ $meeting->id }}"
    data-file-reference-context-project-id="{{ $project->id }}">
    <div class="row">
      <div class="col-lg-8 mb-4 mb-lg-0">
        @include('module-meetings.partials.meeting-notes')
        @include('module-meetings.partials.meeting-actions')
        @include('module-meetings.partials.meeting-records')
        @include('comments.partials.thread', ['commentable' => $meeting])
      </div>

      <div class="col-lg-4">
        @include('module-meetings.partials.overview')
        <x-files.list :owner="$meeting" :files="$files" :shared-files="$sharedFiles" />
      </div>
    </div>
  </div>
@endsection
