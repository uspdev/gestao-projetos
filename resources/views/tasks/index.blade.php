@extends('layouts.app')

@section('title', 'Minhas Tarefas')

@section('content')

  <div class="container-fluid">
    <div class="d-flex align-items-center gap-2 mb-4 flex-wrap">
      <h4 class="mb-0">Minhas Tarefas</h4>
      @include('projects.partials.components.admin-view-toggle-btn', [
          'allViewLabel' => 'Ver todas',
          'myViewLabel' => 'Ver minhas',
          'allViewTitle' => 'Mostrando todas as tarefas',
          'myViewTitle' => 'Mostrando apenas minhas tarefas',
      ])
      @include('tasks.partials.buttons.toggle-layout-btn')
      @include('tasks.partials.buttons.show-done-btn')
    </div>

    @if (session('tasks_view') === 'kanban')
      @include('tasks.partials.kanban.kanban')
    @else
      <div class="row">
        @forelse($tasks as $task)
          <div class="col-md-6 col-lg-4 mb-2">
            @include('tasks.partials.components.preview')
          </div>
        @empty
          <div class="col-12">
            <div class="alert alert-info">Você ainda não possui tarefas atribuídas.</div>
          </div>
        @endforelse
      </div>
    @endif
  </div>
@endsection
