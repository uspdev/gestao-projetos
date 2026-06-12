@extends('emails.layouts.base')

@section('content')
  <p>Você foi removido como responsável da task "{{ $task->title }}" no projeto "{{ $task->project->name }}".</p>
  <p>Responsável pela ação: {{ $actor->name }}.</p>

  @include('emails.partials.action-link', [
      'url' => route('tasks.show', $task),
      'label' => 'Ver tarefa',
  ])
@endsection
