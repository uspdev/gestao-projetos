@extends('emails.layouts.base')

@section('content')
  <p>Voce foi atribuido a task "{{ $task->title }}" no projeto "{{ $task->project->name }}".</p>
  <p>Responsavel pela atribuicao: {{ $actor->name }}.</p>
@endsection
