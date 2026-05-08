<div class="card mb-4 shadow-sm border-top-primary">
  <div class="card-header py-2">
    <span class=""><i class="fas fa-info-circle"></i> Informações</span>
  </div>
  <div class="card-body">
    <ul class="list-unstyled m-0">
      <li class="mb-3 border-bottom pb-2">
        <div class="row no-gutters">
          <div class="col-6 border-right pr-2 d-flex align-items-center">
            <span class="text-muted small mr-2">Prioridade:</span>
            @include('tasks.partials.components.task-priority')
          </div>
          <div class="col-6 pl-2 d-flex align-items-center">
            <span class="text-muted small mr-2">Tags:</span>
            @include('tasks.partials.components.task-tags')
          </div>
        </div>
      </li>

      <li class="mb-0">
        <div class="row no-gutters">
          <div class="col-6 border-right pr-2">
            <span class="text-muted small mr-2">Início:</span>
            <span class="font-weight-bold">
              <x-local-date :date="$task->start_date" />
            </span>
          </div>
          <div class="col-6 pl-2">
            <span class="text-muted small mr-2">Prazo:</span>
            <x-local-date :date="$task->due_date"
              class="{{ $task->due_date && \Carbon\Carbon::parse($task->due_date)->isPast() && $task->status->value !== \App\Enums\Task\TaskStatus::DONE->value ? 'text-danger' : 'text-dark' }}" />
          </div>
        </div>
      </li>
    </ul>
  </div>
</div>
