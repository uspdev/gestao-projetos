@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
  <div class="container-fluid">
    {{-- Card de Informações do Usuário --}}
    @include('users.partials.user-info')

    @if (Auth::id() === $user->id)
      <hr class="my-4">

      @include('projects.partials.index.pinned-projects-section')
      <div class="h5 mb-4"><a href="{{ route('projects.index') }}"> Ver todos os projetos</a></div>

      <hr class="my-4">

      <div class="d-flex align-items-center gap-2 mb-4 flex-wrap">
        <h4 class="mb-0">Minhas Tarefas</h4>
        @include('module-tasks.partials.buttons.toggle-layout-btn')
        @include('module-tasks.partials.buttons.show-done-btn')
        @include('module-tasks.partials.buttons.search-task-form')
    @endif
  </div>
  @if (Auth::id() === $user->id)

    @if (session('tasks_view') === 'kanban')
      @include('module-tasks.partials.kanban.kanban')
    @else
      <div class="row">
        @forelse($tasksByStatus as $task)
          <div class="col-md-6 col-lg-4 mb-2 task-search-item"
            data-task-searchable="{{ strtolower($task->title . ' ' . ($task->project?->name ?? '') . ' ' . ($task->priority?->label() ?? '') . ' ' . $task->users->pluck('name')->implode(' ')) }}">
            @include('module-tasks.partials.components.preview')
          </div>
        @empty
          <div class="col-12">
            <div class="alert alert-info">Você ainda não possui tarefas atribuídas.</div>
          </div>
        @endforelse
      </div>
    @endif
  @endif
  <div class="row mt-3">
    <div class="col-12">
      <div id="tasks-no-results" class="alert alert-info d-none">Nenhuma tarefa encontrada para sua busca.</div>
    </div>
  </div>
@endsection
