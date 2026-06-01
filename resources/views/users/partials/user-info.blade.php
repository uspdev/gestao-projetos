<div class="card mb-4 shadow-sm border-top-primary">
  <div class="card-body d-flex align-items-center">
    {{-- Avatar --}}
    <div class="mr-4 text-secondary">
      <i class="fas fa-user-circle fa-5x"></i>
    </div>

    <div>
      <h3 class="m-0 font-weight-bold text-dark">{{ $user->name }}</h3>
      <p class="text-muted mb-2 fs-5">
        <i class="fas fa-envelope mr-1"></i> {{ $user->email }}
      </p>

      <div class="d-flex align-items-center mt-2">
        {{-- Número USP --}}
        @if ($user->codpes)
          <span class="badge badge-info p-2 mr-2">
            <i class="fas fa-id-card mr-1"></i> Nº USP: {{ $user->codpes }}
          </span>
        @endif

        {{-- Roles --}}
        @if ($user->roles && $user->roles->count() > 0)
          @foreach ($user->roles as $role)
            <span class="badge badge-dark p-2 mr-1">{{ ucfirst($role->name) }}</span>
          @endforeach
        @endif
      </div>
    </div>
  </div>
</div>
