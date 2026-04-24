<li class="list-group-item d-flex justify-content-between align-items-center">
  <div>
    <i class="fas fa-user-circle text-secondary mr-2 fa-lg"></i>
    <a href="{{ route('users.show', $user->id) }}" class="text-dark font-weight-bold">
      {{ $user->name }}
    </a>
  </div>
  <div class="d-flex align-items-center">
    @if (isset($project))
      @if (!empty($canManageMembers))
        <div class="dropdown mr-2">
          <button class="btn btn-sm p-0 border-0 bg-transparent dropdown-toggle" type="button"
            id="member-role-dropdown-{{ $user->id }}" data-toggle="dropdown" aria-haspopup="true"
            aria-expanded="false" title="Alterar role do membro">
            <span class="badge {{ $project->userRole($user)?->color() ?? 'badge-light border text-muted' }}">
              {{ $project->userRole($user)?->label() ?? 'Sem role' }}
            </span>
          </button>
          <div class="dropdown-menu dropdown-menu-right p-2" aria-labelledby="member-role-dropdown-{{ $user->id }}">
            @foreach (\App\Enums\Project\ProjectUserRole::cases() as $role)
              <form method="POST" action="{{ route('projects.members.updateRole', [$project, $user]) }}"
                class="mb-1">
                @csrf
                @method('PATCH')
                <input type="hidden" name="role" value="{{ $role->value }}">
                <button type="submit" class="btn btn-sm btn-block text-left" @disabled($project->userRole($user)?->value === $role->value)>
                  <span class="badge {{ $role->color() }}">
                    {{ $role->label() }}
                  </span>
                  @if ($project->userRole($user)?->value === $role->value)
                    <small class="text-muted ml-1">(atual)</small>
                  @endif
                </button>
              </form>
            @endforeach
          </div>
        </div>
      @else
        <span class="badge {{ $project->userRole($user)?->color() ?? 'badge-light border text-muted' }} mr-2">
          {{ $project->userRole($user)?->label() ?? 'Sem role' }}
        </span>
      @endif
    @else
      <span class="badge badge-light border text-muted mr-2">Sem role</span>
    @endif

    @if (!empty($canManageMembers) && isset($project))
      <form method="POST" action="{{ route('projects.members.destroy', [$project, $user]) }}"
        onsubmit="return confirm('Deseja remover este membro do projeto?');">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-sm btn-outline-danger" title="Remover membro">
          <i class="fas fa-trash"></i>
        </button>
      </form>
    @endif

    @if (!empty($canManageTaskAssignees) && isset($task))
      <form method="POST" action="{{ route('tasks.assignees.destroy', [$task, $user]) }}"
        onsubmit="return confirm('Deseja remover este responsável da tarefa?');">
        @csrf
        @method('DELETE')
        <button type="submit" class="btn btn-sm btn-outline-danger" title="Remover responsável">
          <i class="fas fa-trash"></i>
        </button>
      </form>
    @endif
  </div>
</li>
