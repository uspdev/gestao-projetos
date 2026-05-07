<li class="list-group-item d-flex justify-content-between align-items-center">
  <div>
    <i class="fas fa-user-circle text-secondary mr-2 fa-lg"></i>
    <a href="{{ route('users.show', $user->id) }}" class="text-dark font-weight-bold">
      {{ $user->name }}
    </a>
  </div>
  <div class="d-flex align-items-center">
    @php
      $canManageMembers = isset($project) && auth()->user()->can('storeMember', $project);
      $canManageTaskAssignees = isset($task) && auth()->user()->can('storeAssignee', $task);
      // Variável para indicar se estamos no contexto de uma tarefa,
      // usada para condicionar a exibição do botão de remoção de responsável
      // Isso é neccessario devido a remoção da passagem de parametros específicos
      // para o preview do usuário, como 'canManageMembers' e 'canManageTaskAssignees'
      $isTaskContext = isset($task);
    @endphp

    @if (isset($project))
      @if (!empty($canManageMembers))
        <div class="dropdown mr-2">
          <button class="btn btn-sm p-0 border-0 bg-transparent dropdown-toggle" type="button"
            id="member-role-dropdown-{{ $user->id }}" data-toggle="dropdown" aria-haspopup="true"
            aria-expanded="false" title="Alterar role do membro">
            <span class="badge {{ $project->userRole($user)?->color() ?? 'badge-light border text-muted' }}">
              {{ $project->userRole($user)?->label() ?? 'Sem função' }}
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
          {{ $project->userRole($user)?->label() ?? 'Sem função' }}
        </span>
      @endif
    @else
      <span class="badge badge-light border text-muted mr-2">Sem função</span>
    @endif

    @if (!empty($canManageMembers) && isset($project) && !$isTaskContext)
      @include('projects.partials.buttons.remove-member-btn')
    @endif

    @if (!empty($canManageTaskAssignees) && $isTaskContext)
      @include('tasks.partials.buttons.remove-assignee-btn')
    @endif
  </div>
</li>
