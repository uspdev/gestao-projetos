@php
  $taskTagIds = $task->project->tasksTagsIds($task->newCollection([$task]))[$task->id];
  // Usado pelo collapse do botstrap
  $descriptionDisplayId = 'task-description-display-' . $task->id;
  $descriptionEditId = 'task-description-edit-' . $task->id;
  $isEditingDescription = old('form_context') === 'task-description' && (string) old('task_id') === (string) $task->id;
@endphp

<div id="task-description-{{ $task->id }}" class="card content-surface entity-context-card entity-context-card--task mb-4 shadow-sm"
  tabindex="-1" data-deep-link-target>
  <div class="card-header d-flex justify-content-between align-items-center gap-2 py-2">
    <h6 class="m-0 text-muted mr-2">
      <i class="fas fa-align-left mr-1" aria-hidden="true"></i> Descrição
    </h6>
    @can('update', $task)
      <button type="button" class="btn btn-outline-primary btn-sm py-0" data-toggle="collapse"
        data-target="#{{ $descriptionDisplayId }}, #{{ $descriptionEditId }}" aria-label="Editar descrição">
        <i class="fas fa-edit"></i>
      </button>
    @endcan
  </div>
  <div class="card-body">
    <div class="text-dark text-justify collapse {{ $isEditingDescription ? '' : 'show' }}"
      id="{{ $descriptionDisplayId }}" style="font-size: 1.1rem; line-height: 1.6;">
      @if ($task->description)
        <x-markdown.markdown-content :text="$task->description" />
      @else
        <div class="text-center text-muted p-5 bg-light rounded">
          <i class="fas fa-align-left fa-3x mb-3 text-secondary"></i>
          <h5>Sem descrição</h5>
          <p class="mb-0">Nenhuma descrição foi fornecida para esta tarefa.</p>
        </div>
      @endif
    </div>

    @can('update', $task)
      <div class="collapse {{ $isEditingDescription ? 'show' : '' }}" id="{{ $descriptionEditId }}">
        <form method="POST" action="{{ route('tasks.updateDescription', $task) }}" class="mt-3">
          @csrf
          @method('PATCH')
          <input type="hidden" name="form_context" value="task-description">
          <input type="hidden" name="task_id" value="{{ $task->id }}">

          <label for="{{ $descriptionEditId }}-textarea" class="sr-only">Editar descrição</label>
          <x-form.textarea name="description" :id="$descriptionEditId . '-textarea'" :value="$task->description" groupClass="mb-2" markdown-profile="full" rows="4"
            maxlength="10000" data-file-reference-url="{{ route('files.selectable', ['context_type' => 'task', 'context_id' => $task->id]) }}"
            data-mention-search-url="{{ route('mentions.selectable', ['context_type' => 'task', 'context_id' => $task->id]) }}" />

          <div class="d-flex justify-content-end" style="gap: 0.5rem;">
            <x-form.cancel-button class="btn-sm" data-toggle="collapse"
              data-target="#{{ $descriptionDisplayId }}, #{{ $descriptionEditId }}" />
            <x-form.save-button class="btn btn-primary btn-sm" />
          </div>
        </form>
      </div>
    @endcan
  </div>
</div>
