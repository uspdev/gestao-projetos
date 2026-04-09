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
        $roleLabel = $roleValue ? ucfirst(strtolower($roleValue->value)) : 'Sem role';
    @endphp
    <span class="badge badge-light border text-muted">{{ $roleLabel }}</span>
</li>
