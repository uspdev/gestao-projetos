@extends('layouts.app')

@section('title', 'Minhas Tarefas')

@section('content')
  @php
    $view = $view ?? request()->query('view');
    $kanbanView = $kanbanView ?? $view === 'kanban';
    $showDone = $showDone ?? $kanbanView || request()->boolean('show_done');
  @endphp

  <div class="container-fluid">
    <div class="d-flex align-items-center gap-2 mb-4 flex-wrap">
      <h4 class="mb-0">Minhas Tarefas</h4>
      @include('user-tasks.partials.toggle-layout-btn', ['view' => $view])
      @include('tasks.partials.show-done-btn')
    </div>

    @if ($view === 'kanban')
      @include('user-tasks.partials.kanban')
    @else
      <div class="row">
        @forelse($tasks as $task)
          <div class="col-md-6 col-lg-4 mb-2">
            @include('partials.tasks.preview', ['task' => $task])
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
