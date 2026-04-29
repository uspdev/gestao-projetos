@extends('layouts.app')

@section('title', 'Minhas Tarefas')

@section('content')
  @php
    $kanbanView = $kanbanView ?? request()->query('view') === 'kanban';
    $showDone = $showDone ?? $kanbanView || request()->boolean('show_done');
  @endphp

  <div class="container-fluid">
    <div class="d-flex align-items-center gap-2 mb-4 flex-wrap">
      <h2 class="mb-0">Minhas Tarefas</h2>
      @includeIf('user-tasks.partials.toggle-layout-btn', ['kanbanView' => $kanbanView])
      @include('tasks.partials.show-done-btn', ['showDone' => $showDone])
    </div>

    @if ($kanbanView)
      @includeIf('user-tasks.partials.kanban', ['tasks' => $tasks])
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
