@extends('module-tasks.layouts.task')


@section('title', $title . ' | Detalhes da Tarefa')

@section('task-header')
  <div class="d-flex align-items-center gap-2 flex-grow-1" style="min-width: 0;">
    <x-separator />
    <b class="text-truncate" title="{{ $task->title }}">{{ $task->title }}</b>
    @include('module-tasks.partials.components.update-status')
  </div>

  <div class="ml-auto flex-shrink-0">
    @include('module-tasks.partials.buttons.duplicate-btn')
  </div>
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
