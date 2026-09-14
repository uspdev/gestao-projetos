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

@can('reject', $projectRequest)
  @push('modals')
    <div class="modal fade" id="rejectProjectRequestModal" tabindex="-1"
      aria-labelledby="rejectProjectRequestModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="rejectProjectRequestModalLabel">Rejeitar Solicitação</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>

          <form action="{{ route('projects.requests.reject', [$project, $projectRequest]) }}" method="POST">
            @csrf
            @method('PATCH')

            <div class="modal-body">
              <x-form.textarea name="response" label="Resposta à Solicitação" rows="6" maxlength="10000"
                required />
            </div>

            <div class="modal-footer">
              <x-form.cancel-button data-dismiss="modal" />
              <x-form.save-button class="btn btn-danger" label="Rejeitar Solicitação" />
            </div>
          </form>
        </div>
      </div>
    </div>
  @endpush
@endcan
