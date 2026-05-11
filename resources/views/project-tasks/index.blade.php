@extends('layouts.app')

@section('title', $project->name . ' - Tarefas')

@section('content')
  <div class="card">
    @include('projects.partials.show.show-header')
    <div class="card-body">
      <div class="card mb-4 shadow-sm">
        <div class="card-header h5">
          <i class="fas fa-tasks"></i> Tarefas
          @include('tasks.partials.buttons.create-task-btn')
          @include('tasks.partials.buttons.toggle-layout-btn')
          @include('tasks.partials.buttons.show-done-btn')
        </div>

        <div class="card-body">
          @if (session('tasks_view') === 'kanban')
            @include('tasks.partials.kanban.kanban')
          @else
            @include('projects.partials.show.show-card-tasks-table')
          @endif
        </div>
      </div>
    </div>
  </div>
  @include('tasks.partials.components.task-form-modal')
@endsection
