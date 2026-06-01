@extends('emails.layouts.base')

@section('content')
  @if ($isCancelled)
    <p>A reuniao "{{ $meeting->title }}" foi cancelada.</p>
  @else
    <p>A reuniao "{{ $meeting->title }}" foi atualizada.</p>
  @endif
  <p>Data/hora: {{ $meeting->scheduled_at?->format('d/m/Y H:i') ?? '-' }}.</p>
  <p>Local: {{ $meeting->location ?? '-' }}.</p>
  <p>Responsavel: {{ $actor->name }}.</p>
@endsection
