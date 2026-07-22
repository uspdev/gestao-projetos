@extends('projects.layouts.project')
@php
  $tasksDone = session('tasks_done');
@endphp
@section('title', $title . ' | Tarefas')

@section('project-content')
  @php
    $tasksMine = session('tasks_mine', '0');
    $incompleteTasksCount = $project->getIncompleteTasksCount();
  @endphp

  <div class="card shadow-sm">
    <div class="card-header h5 py-2 d-flex align-items-center justify-content-start gap-2" style="background-color: lightCyan;">
      <a href="{{ route('projects.tasks.index', $project) }}" class="text-decoration-none text-dark">
        <i class="fas fa-tasks"></i> {{ $tasksMine ? 'Minhas' : 'Todas as' }} tarefas
        @if ($tasksDone)
          <x-separator />
          <span class="ml-1 text-muted">Concluídas</span>
        @endif
      </a>

      <span class="badge badge-pill badge-secondary flex-shrink-0">{{ $incompleteTasksCount }}</span>
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
