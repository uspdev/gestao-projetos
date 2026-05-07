@extends('layouts.app')

@section('title', $project->name . ' - Tarefas')

@section('content')
  <div class="card">
    @include('projects.partials.show.show-header')
    <div class="card-body">
      @php
        $view = $view ?? request()->query('view', 'kanban');
        $kanbanView = $kanbanView ?? $view === 'kanban';
        $showDone = $showDone ?? $kanbanView || request()->boolean('show_done');
      @endphp

      <div class="row">
          @if ($kanbanView)
            @include('project-tasks.partials.kanban')
          @else
            @include('projects.partials.show.show-card-tasks-table')
          @endif
      </div>
    </div>
  </div>
  @include('tasks.partials.task-form-modal')
@endsection
