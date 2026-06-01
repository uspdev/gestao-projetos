@extends('emails.layouts.base')

@section('content')
  <p>A task "{{ $task->title }}" foi concluida.</p>
  <p>Projeto: "{{ $task->project->name }}".</p>
  <p>Conclusao registrada por: {{ $actor->name }}.</p>

  @include('emails.partials.action-link', [
      'url' => route('tasks.show', $task),
      'label' => 'Ver tarefa',
  ])
@endsection
