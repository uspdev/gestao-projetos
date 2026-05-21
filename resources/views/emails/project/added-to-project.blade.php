@extends('emails.layouts.base')

@section('content')
  <p>Voce foi adicionado ao projeto "{{ $project->name }}".</p>
  <p>Responsavel pela acao: {{ $actor->name }}.</p>
@endsection
