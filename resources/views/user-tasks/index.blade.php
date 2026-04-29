@extends('layouts.app')

@section('title', 'Minhas Tarefas')

@section('content')
  <div class="container-fluid">
    <div class="d-flex align-items-center gap-2 mb-4 flex-wrap">
      <h2 class="mb-0">Minhas Tarefas</h2>
      @include('tasks.partials.show-done-btn')
    </div>

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
  </div>
@endsection
