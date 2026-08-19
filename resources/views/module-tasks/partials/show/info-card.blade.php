<div class="card options-surface entity-context-card entity-context-card--task mb-4 shadow-sm border-top-primary">
  <div class="card-header d-flex align-items-center justify-content-between py-2">
    <h6 class="m-0 text-muted mr-2">
      <i class="fas fa-info-circle mr-1" aria-hidden="true"></i> Informações
    </h6>

    <div class="d-flex align-items-center">
      @include('module-tasks.partials.buttons.delete-btn')
    </div>
  </div>
  <div class="card-body">
    <ul class="list-unstyled m-0">
      <li class="mb-3 border-bottom pb-2">
        <div class="row no-gutters">
          <div class="col-4 border-right pr-2 d-flex align-items-center">
            <span class="text-muted small mr-2">Prioridade:</span>
            @include('module-tasks.partials.components.task-priority')
          </div>
          <div class="col-8 pl-2 d-flex align-items-center">
            <span class="text-muted small mr-2">Tags:</span>
            @include('module-tasks.partials.components.task-tags')
          </div>
        </div>
      </li>

      <li class="mb-0">
        <div class="row no-gutters">
          <div class="col-4 border-right">
            <span class="text-muted small mr-2">Início:</span>
            <x-local-date :date="$task->start_date" />
          </div>
          <div class="col-4 border-right pl-2">
            <span class="text-muted small mr-2">Prazo:</span>
            <x-local-date :date="$task->due_date" :overdue="$task->isOverdue()" />
          </div>
          <div class="col-4 pl-2">
            <span class="text-muted small mr-2">Conclusão:</span>
            <x-local-date :date="$task->completed_at" />
          </div>
        </div>
      </li>
    </ul>
  </div>
</div>
