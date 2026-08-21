@extends('emails.layouts.base')

@section('content')
  <p>Você foi atribuído à tarefa "{{ $task->title }}" no projeto "{{ $task->project->name }}".</p>
  <p>Responsável pela atribuição: {{ $actor->name }}.</p>

  @include('emails.partials.action-link', [
      'url' => deep_link('tasks.show', $task),
      'label' => 'Ver tarefa',
  ])
@endsection
