@extends('projects.layouts.project')

@section('title', $title . ' | Detalhes da Solicitação')

@section('project-content')
  <div class="card shadow-sm">
    @include('project-requests.partials.header')

    <div class="card-body">
      <dl class="row mb-0">
        <dt class="col-md-3">Sistema cliente</dt>
        <dd class="col-md-9">{{ $projectRequest->clientSystem->name }}</dd>

        <dt class="col-md-3">Recebida em</dt>
        <dd class="col-md-9"><x-local-datetime :date="$projectRequest->created_at" /></dd>

        <dt class="col-md-3">Descrição da Solicitação</dt>
        <dd class="col-md-9 text-break">{!! nl2br(e($projectRequest->description)) !!}</dd>

        @if ($projectRequest->source_url)
          <dt class="col-md-3">URL de origem</dt>
          <dd class="col-md-9 text-break">
            <a href="{{ $projectRequest->source_url }}" target="_blank" rel="noopener noreferrer">
              {{ $projectRequest->source_url }}
            </a>
          </dd>
        @endif

        @if ($projectRequest->response !== null)
          <dt class="col-md-3">Resposta à Solicitação</dt>
          <dd class="col-md-9 text-break">{!! nl2br(e($projectRequest->response)) !!}</dd>
        @endif

        @if ($projectRequest->evaluated_at)
          <dt class="col-md-3">Avaliada em</dt>
          <dd class="col-md-9"><x-local-datetime :date="$projectRequest->evaluated_at" /></dd>
        @endif

        @if ($projectRequest->task)
          <dt class="col-md-3">Tarefa resultante</dt>
          <dd class="col-md-9">
            <a href="{{ route('tasks.show', $projectRequest->task) }}">
              {{ $projectRequest->task->title }}
            </a>
          </dd>
        @endif
      </dl>
    </div>
  </div>
@endsection
