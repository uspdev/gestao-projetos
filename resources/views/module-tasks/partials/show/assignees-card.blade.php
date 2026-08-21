<div class="card options-surface entity-context-card entity-context-card--task mb-4 shadow-sm">
  <div class="card-header d-flex align-items-center py-2">
    <h6 class="m-0 text-muted mr-2">
      <i class="fas fa-users mr-1" aria-hidden="true"></i> Responsáveis
    </h6>
    @include('module-tasks.partials.buttons.add-assignee-btn')
  </div>
  <div class="card-body p-0">
    <ul class="list-group list-group-flush">
      @forelse($task->users as $user)
        <li class="list-group-item d-flex gap-2 justify-content-between align-items-center">
          @include('users.partials.preview')
          <div class="d-flex align-items-center gap-2">
            @include('users.partials.user-task-badge', ['project' => $task->project])
            @include('users.partials.remove-member-assignee-btn')
          </div>
        </li>
      @empty
        <li class="list-group-item text-muted font-italic small text-center py-3">
          Nenhum usuário atribuído.
        </li>
      @endforelse
    </ul>
  </div>
</div>
