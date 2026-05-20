@extends('layouts.app')

@section('title', 'Detalhes da Tarefa')

@section('content')
  <div class="card mb-4 shadow-sm">
    @include('module-tasks.partials.show.header')
    <div class="card-body">
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
    </div>
  </div>
  @include('module-tasks.partials.components.task-form-modal', ['project' => $task->project])
@endsection
