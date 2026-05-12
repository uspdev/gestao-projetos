@can('update', $project)
  <div class="dropdown">
    <button class="btn btn-sm btn-outline-secondary" type="button"
      id="project-visibility-dropdown-{{ $project->id }}"
      data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
      title="Alterar visibilidade do projeto">

      {{ $project->visibility?->label() ?? 'Nao definido' }}

      <i class="fas fa-caret-down ml-1"></i>
    </button>

    <div class="dropdown-menu dropdown-menu-right p-2" aria-labelledby="project-visibility-dropdown-{{ $project->id }}">
      @foreach (\App\Enums\Project\ProjectVisibility::cases() as $visibility)
        <form method="POST" action="{{ route('projects.updateVisibility', $project) }}" class="mb-1">
          @csrf
          @method('PATCH')
          <input type="hidden" name="visibility" value="{{ $visibility->value }}">
          <button type="submit" class="btn btn-sm btn-block text-left" @disabled($project->visibility?->value === $visibility->value)>
            <span class="badge badge-light border">{{ $visibility->label() }}</span>
            @if ($project->visibility?->value === $visibility->value)
              <small class="text-muted ml-1">(atual)</small>
            @endif
          </button>
        </form>
      @endforeach
    </div>
  </div>
@else
  <span class="btn btn-sm btn-outline-secondary">
    {{ $project->visibility?->label() ?? 'Nao definido' }}
  </span>
@endcan
