<li class="list-group-item d-flex justify-content-between align-items-center">
    <div>
        <i class="fas fa-user-circle text-secondary mr-2 fa-lg"></i>
        <a href="{{ route('users.show', $user->id) }}" class="text-dark font-weight-bold">
            {{ $user->name }}
        </a>
    </div>
    @php
        $projectMember = isset($project) ? $project->users->firstWhere('id', $user->id) : null;
        $roleValue = $projectMember?->pivot?->role ?? ($user->pivot->role ?? null);
        $roleEnum =
            $roleValue instanceof \App\Enums\Project\ProjectUserRole
                ? $roleValue
                : \App\Enums\Project\ProjectUserRole::tryFrom((string) $roleValue);
        $roleLabel = $roleEnum?->label() ?? 'Sem role';
        $roleColor = $roleEnum?->color() ?? 'badge-light border text-muted';
    @endphp
    <div class="d-flex align-items-center">
        <span class="badge {{ $roleColor }} mr-2">{{ $roleLabel }}</span>

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
    </div>
</li>
