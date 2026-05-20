@can('create', [\App\Models\Task::class, $project])
  <button type="button" class="btn btn-sm btn-outline-success py-0" data-toggle="modal" data-target="#modalTaskForm"
    data-task-modal="task-form" data-mode="create" data-action="{{ route('projects.tasks.store', $project) }}"
    title="Adicionar task">
    <i class="fas fa-plus"></i>
  </button>
@endcan
