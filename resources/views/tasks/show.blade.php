@extends('layouts.app')

@section('title', 'Detalhes da Tarefa')

@section('content')
  {{-- Card único envolvendo todo o conteúdo --}}
  <div class="card mb-4 shadow-sm">
    @include('tasks.partials.show.header')

    <div class="card-body">
      <div class="row">
        {{-- COLUNA PRINCIPAL: Título e Descrição --}}
        <div class="col-md-8">
          @include('tasks.partials.show.main-card', ['task' => $task])
        </div>

        {{-- COLUNA LATERAL (Direita): Metadados e Responsáveis --}}
        <div class="col-md-4">
          @include('tasks.partials.show.info-card', ['task' => $task])
          @include('tasks.partials.show.assignees-card', ['task' => $task])

        </div>
      </div>
    </div>
  </div>

  @include('tasks.partials.task-form-modal', ['project' => $task->project])


@endsection
