@extends('emails.layouts.base')

@section('content')
  @php
    $contextLabel =
        $commentable instanceof \App\Models\Project
            ? 'Projeto'
            : ($commentable instanceof \App\Models\Task
                ? 'Task'
                : 'Reuniao');

    $contextName = $commentable instanceof \App\Models\Project ? $commentable->name : $commentable->title;
  @endphp

  <p>Novo comentario em {{ $contextLabel }} "{{ $contextName }}".</p>
  <p>Autor: {{ $actor->name }}.</p>
  <p>Comentario:</p>
  <p>{{ $comment->text }}</p>
@endsection
