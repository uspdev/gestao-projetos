@extends('layouts.project')

@section('title', $project->name . ' - Tarefas')

@section('project-content')
  <div class="card shadow-sm">
    <div class="card-header h5 py-2" style="background-color: lightCyan;">
      <a href="{{ route('projects.tasks.index', $project) }}" class="text-decoration-none text-dark">
        <i class="fas fa-tasks"></i> Tarefas
      </a>
      <span class="badge badge-pill badge-secondary">{{ $project->tasks->count() }}</span>
      @include('module-tasks.partials.buttons.create-task-btn')

      @section('task-header') @show
    </div>

    <div class="card-body p-2">
      @yield('task-content')
    </div>
  </div>
@endsection

@push('modals')
  @include('module-tasks.partials.components.task-form-modal')
@endpush
