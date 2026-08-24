<div>
  <i class="fas fa-user-circle text-secondary fa-lg"></i>
  <a href="{{ route('users.show', $user) }}" class="text-dark">
    {{ $user->name }}
  </a>
</div>
