@extends('emails.layouts.base')

@section('content')
  <p>Criado por: {{ $actor->name }}.</p>
  <p>Novo comentário em {{ $contextLabel }} "{{ $contextName }}".</p>
  <p>Comentário:</p>
  <p>{{ $comment->text }}</p>

  @include('emails.partials.action-link', [
      'url' => $actionUrl,
      'label' => $actionLabel,
  ])
@endsection
