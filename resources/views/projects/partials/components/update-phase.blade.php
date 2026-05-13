@can('update', $project)
  <div class="dropdown">
    <button class="btn btn-sm {{ $project->phase?->color() ?? 'badge-light text-dark' }}" type="button"
      id="project-phase-dropdown-{{ $project->id }}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
      title="Alterar fase do projeto">

      {{ $project->phase?->label() ?? 'Nao definido' }}

      <i class="fas fa-caret-down ml-1"></i>
    </button>

    <div class="dropdown-menu dropdown-menu-right p-2" aria-labelledby="project-phase-dropdown-{{ $project->id }}">
      @foreach (\App\Enums\Project\ProjectPhase::cases() as $phase)
        <form method="POST" action="{{ route('projects.updatePhase', $project) }}" class="mb-1">
          @csrf
          @method('PATCH')
          <input type="hidden" name="phase" value="{{ $phase->value }}">
          <button type="submit" class="btn btn-sm btn-block text-left" @disabled($project->phase?->value === $phase->value)>
            <span class="badge {{ $phase->color() }}">{{ $phase->label() }}</span>
            @if ($project->phase?->value === $phase->value)
              <small class="text-muted ml-1">(atual)</small>
            @endif
          </button>
        </form>
      @endforeach
    </div>
  </div>
@else
  <span class="btn btn-sm {{ $project->phase?->color() ?? 'badge-light text-dark' }}">
    {{ $project->phase?->label() ?? 'Nao definido' }}
  </span>
@endcan
