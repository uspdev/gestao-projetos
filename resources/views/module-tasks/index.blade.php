@extends('layouts.module-task')

@section('task-header')
  @include('module-tasks.partials.buttons.toggle-layout-btn')
  @include('module-tasks.partials.buttons.show-done-btn')
@endsection

@section('task-content')
  @if (session('tasks_view') === 'kanban')
    @include('module-tasks.partials.kanban.kanban')
  @else
    @include('module-tasks.partials.show.card-tasks-table')
  @endif
@endsection
