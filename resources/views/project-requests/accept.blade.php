@extends('projects.layouts.project')

@section('title', $title . ' | Aceitar Solicitação')

@section('project-content')
  <div class="card shadow-sm">
    @include('project-requests.partials.header')

    <div class="card-body">
      <p class="text-muted">
        Revise os dados antes de incorporar a Solicitação como uma nova Tarefa.
      </p>

      <form action="{{ route('projects.requests.accept.store', [$project, $projectRequest]) }}" method="POST">
        @csrf

        <x-form.input name="title" label="Título da Tarefa" :value="$projectRequest->title" required
          minlength="3" maxlength="120" />

        <x-form.textarea name="description" label="Descrição da Tarefa" :value="$projectRequest->description"
          markdown-profile="full" rows="8" maxlength="10000" />

        <div class="row">
          <div class="col-md-4">
            <div class="form-group mb-3">
              <label for="status">Status <span class="text-danger">*</span></label>
              <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
                @foreach (\App\Enums\Task\TaskStatus::cases() as $status)
                  <option value="{{ $status->value }}" @selected(old('status', \App\Enums\Task\TaskStatus::NEW->value) === $status->value)>
                    {{ $status->label() }}
                  </option>
                @endforeach
              </select>
              @error('status')
                <div class="invalid-feedback d-block">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="col-md-4">
            <div class="form-group mb-3">
              <label for="priority">Prioridade</label>
              <select name="priority" id="priority" class="form-control @error('priority') is-invalid @enderror">
                <option value="">Selecione...</option>
                @foreach (\App\Enums\Task\TaskPriority::cases() as $priority)
                  <option value="{{ $priority->value }}" @selected((string) old('priority') === (string) $priority->value)>
                    {{ $priority->label() }}
                  </option>
                @endforeach
              </select>
              @error('priority')
                <div class="invalid-feedback d-block">{{ $message }}</div>
              @enderror
            </div>
          </div>

          <div class="col-md-4">
            <div class="form-group mb-3">
              <label for="task-assignee-id">Responsável <span class="text-muted">(opcional)</span></label>
              <select name="assignee_id" id="task-assignee-id"
                class="form-control @error('assignee_id') is-invalid @enderror">
                <option value="">Sem responsável</option>
                @foreach ($availableTaskAssignees as $assignee)
                  <option value="{{ $assignee->id }}" @selected((int) old('assignee_id') === (int) $assignee->id)>
                    {{ $assignee->name }} ({{ $assignee->email }})
                  </option>
                @endforeach
              </select>
              @error('assignee_id')
                <div class="invalid-feedback d-block">{{ $message }}</div>
              @enderror
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-md-6">
            <x-form.input type="date" name="start_date" label="Data de Início" />
          </div>
          <div class="col-md-6">
            <x-form.input type="date" name="due_date" label="Data de Entrega (Prazo)" />
          </div>
        </div>

        <div class="form-group mb-3">
          <label for="task-tags">Tags</label>
          <select name="tags[]" id="task-tags" multiple class="form-control select2-tags">
            @foreach ($availableTaskTags as $tag)
              <option value="{{ $tag->id }}" @selected(in_array($tag->id, array_map('intval', old('tags', [])), true))>
                {{ $tag->name }}
              </option>
            @endforeach
          </select>
          @error('tags')
            <div class="invalid-feedback d-block">{{ $message }}</div>
          @enderror
          @error('tags.*')
            <div class="invalid-feedback d-block">{{ $message }}</div>
          @enderror
        </div>

        <x-form.textarea name="response" label="Resposta à Solicitação (opcional)" rows="4"
          maxlength="10000" />

        <div class="d-flex justify-content-end gap-2">
          <a href="{{ route('projects.requests.show', [$project, $projectRequest]) }}" class="btn btn-secondary">
            Cancelar
          </a>
          <x-form.save-button class="btn btn-success" label="Criar Tarefa e aceitar Solicitação" />
        </div>
      </form>
    </div>
  </div>
@endsection

@push('scripts')
  @include('module-tasks.partials.scripts.multi-select-script')
@endpush
