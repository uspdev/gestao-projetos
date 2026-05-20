@extends('layouts.module-task')

@section('task-header')
  @include('module-tasks.partials.buttons.create-task-btn')
  @include('module-tasks.partials.buttons.toggle-layout-btn')
  @include('module-tasks.partials.buttons.show-done-btn')
@endsection

@section('task-content')
  @if (session('tasks_view') === 'kanban')
    @include('module-tasks.partials.kanban.kanban')
  @else
    @include('projects.partials.show.show-card-tasks-table')
  @endif

  @include('module-tasks.partials.components.task-form-modal')
@endsection
