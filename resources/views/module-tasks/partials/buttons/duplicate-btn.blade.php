@can('duplicate', $task)
  <button type="button" class="btn btn-outline-secondary btn-sm py-0" data-toggle="modal"
    data-target="#duplicate-task-modal-{{ $task->id }}" title="Duplicar tarefa" aria-label="Duplicar tarefa">
    <i class="fas fa-copy"></i>
  </button>

  @push('modals')
    @include('duplicates.modals.task')
  @endpush
@endcan
