@extends('layouts.app')

@section('title', 'Detalhes da Tarefa')

@section('content')
  <div class="card mb-4 shadow-sm">
    @include('tasks.partials.show.header')
    <div class="card-body">
      <div class="row">
        <div class="col-md-8">
          @include('tasks.partials.show.main-card')
          @include('comments.partials.thread', ['commentable' => $task])
        </div>
        <div class="col-md-4">
          @include('tasks.partials.show.info-card')
          @include('tasks.partials.show.assignees-card')
        </div>
      </div>
    </div>
  </div>
  @include('tasks.partials.components.task-form-modal', ['project' => $task->project])
@endsection
