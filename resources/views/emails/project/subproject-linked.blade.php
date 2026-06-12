@extends('emails.layouts.base')

@section('content')
  <p>O projeto "{{ $subproject->name }}" foi vinculado como subprojeto.</p>
  <p>Projeto pai: "{{ $parentProject->name }}".</p>
  <p>Responsável pela vinculação: {{ $actor->name }}.</p>

  @include('emails.partials.action-link', [
      'url' => route('projects.show', $parentProject) . '?view=subprojects',
      'label' => 'Ver projeto organizacional',
  ])

  @include('emails.partials.action-link', [
      'url' => route('projects.show', $subproject),
      'label' => 'Ver subprojeto',
  ])
@endsection
