@extends('layouts.app')

@section('title', $project->name . ' - Tarefas')

@section('content')
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center gap-2">
      <div class="h4 mb-0">
        <a href="{{ route('projects.index') }}" class="text-decoration-none text-secondary">
          <i class="fas fa-home"></i>
        </a>
        <x-separator />
        <a href="{{ route('projects.show', $project) }}" class="text-decoration-none text-secondary">
          {{ $project->name }}
        </a>
        <x-separator /> Tarefas
      </div>

      <div class="d-flex align-items-center gap-2">
        @include('projects.partials.show-tag-badges')
        @include('projects.partials.edit-btn')
        @include('projects.partials.update-status')
        @include('projects.partials.delete-btn')
      </div>
    </div>
    <div class="card-body">
      @php
        $view = $view ?? request()->query('view', 'kanban');
        $kanbanView = $kanbanView ?? $view === 'kanban';
        $showDone = $showDone ?? $kanbanView || request()->boolean('show_done');
      @endphp

      <div class="row">
        <div class="@if ($kanbanView) col-md-12 @else col-md-8 @endif">
          @if ($kanbanView)
            @include('project-tasks.partials.kanban')
          @else
            @include('projects.partials.show-card-tasks-table')
          @endif
        </div>
        @if (!$kanbanView)
          <div class="col-md-4">
            @include('projects.partials.show-card-membros')
            @include('projects.partials.show-card-descricao')
          </div>
        @endif
      </div>
    </div>
  </div>
  @include('tasks.partials.task-form-modal')
@endsection
