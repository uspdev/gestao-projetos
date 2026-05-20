@extends('layouts.project-meeting')

@section('title', $project->name . ' - Reunioes')

@section('meeting-content')
  @if ($meetings->isEmpty())
    <div class="alert alert-light border text-muted mb-0">Nenhuma reuniao cadastrada.</div>
  @else
    <div class="row">
      @foreach ($meetings as $meeting)
        <div class="col-md-6 mb-4">
          @include('module-meetings.partials.index-item')
        </div>
      @endforeach
    </div>
  @endif

@endsection
