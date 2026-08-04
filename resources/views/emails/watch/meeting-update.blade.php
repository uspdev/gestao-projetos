@extends('emails.layouts.base')

@section('content')
  <p>Olá, {{ $recipient->name }}.</p>

  <p>{{ rtrim($summary, '.') }}: <strong>{{ $meeting->title }}</strong>.</p>
  <p>Responsável pela alteração: {{ $actor->name }}.</p>

  @include('emails.partials.action-link', [
      'url' => $meeting->watchUrl(),
      'label' => 'Abrir reunião',
  ])

  <p>Você pode desativar essas notificações na página da Reunião.</p>
@endsection
