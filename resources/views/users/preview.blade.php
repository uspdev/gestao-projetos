<li class="list-group-item d-flex justify-content-between align-items-center">
    <div>
        <i class="fas fa-user-circle text-secondary mr-2 fa-lg"></i>
        <a href="{{ route('users.show', $user->id) }}" class="text-dark font-weight-bold">
            {{ $user->name }}
        </a>
    </div>
    <span class="badge badge-light border text-muted">{{ $user->email }}</span>
</li>