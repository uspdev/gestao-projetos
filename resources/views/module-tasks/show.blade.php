@extends('module-tasks.layouts.task')


@section('title', 'Detalhes da Tarefa')

@section('task-header')
  <x-separator /> <b>{{ $task->title }}</b>
  @include('module-tasks.partials.components.update-status')
@endsection

@section('task-content')
  <div class="row">
    <div class="col-md-8">
      @include('module-tasks.partials.show.main-card')
      @include('comments.partials.thread', ['commentable' => $task])
    </div>
    <div class="col-md-4">
      @include('module-tasks.partials.show.info-card')
      @include('module-tasks.partials.show.assignees-card')
    </div>
  </div>
@endsection
