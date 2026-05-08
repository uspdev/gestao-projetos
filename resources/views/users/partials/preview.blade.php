<div>
  <i class="fas fa-user-circle text-secondary fa-lg"></i>
  <a href="{{ route('users.show', $user->id) }}" class="text-dark font-weight-bold">
    {{ $user->name }}
  </a>
</div>

<div class="d-flex align-items-center">
  @include('projects.partials.buttons.member-role-dropdown')
  @include('users.partials.remove-member-assignee-btn')
</div>
