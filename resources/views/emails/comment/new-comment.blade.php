@extends('emails.layouts.base')

@section('content')
  <p>Novo comentário em {{ $contextLabel }} "{{ $contextName }}".</p>
  <p>Autor: {{ $actor->name }}.</p>
  <p>Comentário:</p>
  <p>{{ $comment->text }}</p>

  @include('emails.partials.action-link', [
      'url' => $actionUrl,
      'label' => $actionLabel,
  ])
@endsection
