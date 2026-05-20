@php
  $taskTagIds = $task->project->tasksTagsIds($task->newCollection([$task]))[$task->id];
@endphp

<div class="card mb-4 shadow-sm">
  <div class="card-header h5 d-flex justify-content-between align-items-center gap-2 py-2">
    <div class="d-flex justify-content-between gap-2">
      <span>{{ $task->title }}</span>
      @include('module-tasks.partials.components.update-status')
    </div>

    <div>
      @include('module-tasks.partials.buttons.edit-btn')
      @include('module-tasks.partials.buttons.delete-btn')
    </div>
  </div>
  <div class="card-body">
    <div class="text-dark text-justify" style="font-size: 1.1rem; line-height: 1.6;">
      @if ($task->description)
        <x-markdown-content :text="$task->description" />
      @else
        <div class="text-center text-muted p-5 bg-light rounded">
          <i class="fas fa-align-left fa-3x mb-3 text-secondary"></i>
          <h5>Sem descrição</h5>
          <p class="mb-0">Nenhuma descrição foi fornecida para esta tarefa.</p>
        </div>
      @endif
    </div>
  </div>
</div>
