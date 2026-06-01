@extends('layouts.project-meeting')

@section('meeting-header')
  <x-separator /> Editando <b>{{ $meeting->title }}</b>
@endsection

@section('meeting-content')
  @include('module-meetings.partials.form', [
      'action' => route('projects.meetings.update', [$project, $meeting]),
      'method' => 'PUT',
      'showNotesField' => false,
  ])
@endsection
