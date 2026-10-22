@php
  $descriptionDisplayId = $descriptionDisplayId ?? ('task-description-display-' . $task->id);
  $descriptionEditId = $descriptionEditId ?? ('task-description-edit-' . $task->id);
@endphp

@can('update', $task)
  <button type="button" class="btn btn-outline-primary btn-sm py-0" data-toggle="collapse"
    data-target="#{{ $descriptionDisplayId }}, #{{ $descriptionEditId }}" aria-label="Editar descrição">
    <i class="fas fa-edit"></i>
  </button>
@endcan
