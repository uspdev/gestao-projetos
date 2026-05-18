@can('update', $project)
  <div class="dropdown position-static">
    <button class="btn btn-sm {{ $project->permission_inheritance?->color() ?? 'badge-light text-dark' }}" type="button"
      id="project-permission-inheritance-dropdown-{{ $project->id }}" data-toggle="dropdown" aria-haspopup="true"
      aria-expanded="false" title="Alterar heranca de permissoes">

      {{ $project->permission_inheritance?->label() ?? 'Nao definido' }}

      <i class="fas fa-caret-down ml-1"></i>
    </button>

    <div class="dropdown-menu dropdown-menu-right p-2"
      aria-labelledby="project-permission-inheritance-dropdown-{{ $project->id }}">
      @foreach (\App\Enums\Project\ProjectPermissionInheritance::cases() as $permissionInheritance)
        @continue($permissionInheritance !== \App\Enums\Project\ProjectPermissionInheritance::FULL) {{-- Comentar essa linha se for permitir outras opcoes de herança --}}
        <form method="POST" action="{{ route('projects.updatePermissionInheritance', $project) }}" class="mb-1">
          @csrf
          @method('PATCH')
          <input type="hidden" name="permission_inheritance" value="{{ $permissionInheritance->value }}">
          <button type="submit" class="btn btn-sm btn-block text-left" @disabled($project->permission_inheritance?->value === $permissionInheritance->value)>
            <span class="badge {{ $permissionInheritance->color() }}">{{ $permissionInheritance->label() }}</span>
            @if ($project->permission_inheritance?->value === $permissionInheritance->value)
              <small class="text-muted ml-1">(atual)</small>
            @endif
          </button>
        </form>
      @endforeach
    </div>
  </div>
@else
  <span class="btn btn-sm {{ $project->permission_inheritance?->color() ?? 'badge-light text-dark' }}">
    {{ $project->permission_inheritance?->label() ?? 'Nao definido' }}
  </span>
@endcan
