@extends('emails.layouts.base')

@section('content')
  <p>Uma nova reuniao foi criada: "{{ $meeting->title }}".</p>
  <p>Data/hora: {{ $meeting->scheduled_at?->format('d/m/Y H:i') ?? '-' }}.</p>
  <p>Local: {{ $meeting->location ?? '-' }}.</p>
  <p>Responsavel: {{ $actor->name }}.</p>
@endsection
