@extends('emails.layouts.base')

@section('content')
  <p>Você foi adicionado ao projeto "{{ $project->name }}".</p>
  <p>Responsável pela ação: {{ $actor->name }}.</p>

  @include('emails.partials.action-link', [
      'url' => deep_link('projects.show', $project),
      'label' => 'Ver projeto',
  ])
@endsection
