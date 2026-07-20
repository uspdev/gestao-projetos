@extends('emails.layouts.base')

@section('content')
  @php
      $meeting->loadMissing('projects');
      $project = $meeting->projects->first();
  @endphp

  <p>Uma nova reunião foi criada: "{{ $meeting->title }}".</p>
  <p>Data/hora: {{ $meeting->scheduled_at?->format('d/m/Y H:i') ?? '-' }}.</p>
  <p>Local: {{ $meeting->location ?? '-' }}.</p>
  <p>Responsável: {{ $actor->name }}.</p>

  @if ($project)
    @include('emails.partials.action-link', [
        'url' => route('projects.meetings.show', [$project, $meeting]),
        'label' => 'Ver reuniao',
    ])
  @endif
@endsection
