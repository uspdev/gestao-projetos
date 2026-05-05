<div class="card mb-4 shadow-sm border-top-primary">
  <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
    <h6 class="m-0 text-muted">
      <i class="fas fa-users mr-1"></i> Responsáveis
    </h6>
    @includeWhen(auth()->user()->can('storeAssignee', $task), 'tasks.partials.add-assignee-btn')
  </div>
  <ul class="list-group list-group-flush">
    @forelse($task->users as $user)
      @include('users.preview', [
          'user' => $user,
          'project' => $task->project,
          'task' => $task,
          'canManageTaskAssignees' => auth()->user()->can('storeAssignee', $task),
      ])
    @empty
      <li class="list-group-item text-muted font-italic small text-center py-3">
        Nenhum usuário atribuído.
      </li>
    @endforelse
  </ul>
</div>
