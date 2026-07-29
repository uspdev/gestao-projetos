@extends('emails.layouts.base')

@section('content')
  <p>Olá, {{ $recipient->name }}.</p>

  <p>Estas atividades ocorreram em itens que você observa:</p>

  <ul>
    @foreach ($notifications as $notification)
      <li style="margin-bottom: 12px;">
        <strong>{{ $notification->title }}</strong><br>
        {{ $notification->summary }}
        @if ($notification->actor)
          — por {{ $notification->actor->name }}
        @endif
        <br>
        <small>{{ $notification->occurred_at?->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</small>
        @if ($notification->details)
          <div style="white-space: pre-wrap; margin-top: 6px;">{{ $notification->details }}</div>
        @endif
        @if ($notification->url)
          — <a href="{{ $notification->url }}">Abrir</a>
        @endif
      </li>
    @endforeach
  </ul>

  <p>Você pode alterar essa preferência nas configurações do projeto ou na página de cada tarefa e reunião.</p>
@endsection
