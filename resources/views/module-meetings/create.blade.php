@extends('layouts.project-meeting')

@section('title', $project->name . ' - Nova reuniao')

@section('meeting-header')
  <x-separator /> Nova reuniao
@endsection

@section('meeting-content')
  @include('module-meetings.partials.form', [
      'action' => route('projects.meetings.store', $project),
      'method' => 'POST',
      'meeting' => null,
  ])
@endsection
