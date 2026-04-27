@can('update', $project)
  <div class="dropdown">
    <button class="btn btn-sm {{ $project->status->color() }}" type="button"
      id="project-status-dropdown-{{ $project->id }}"
      data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
      title="Alterar status do projeto">

      {{ $project->status->label() }}

      <i class="fas fa-chevron-down ml-1"></i>
    </button>

    <div class="dropdown-menu dropdown-menu-right p-2" aria-labelledby="project-status-dropdown-{{ $project->id }}">
      @foreach (\App\Enums\Project\ProjectStatus::cases() as $status)
        <form method="POST" action="{{ route('projects.updateStatus', $project) }}" class="mb-1">
          @csrf
          @method('PATCH')
          <input type="hidden" name="status" value="{{ $status->value }}">
          <button type="submit" class="btn btn-sm btn-block text-left" @disabled($project->status->value === $status->value)>
            <span class="badge {{ $status->color() }}">
              {{ $status->label() }}
            </span>
            @if ($project->status->value === $status->value)
              <small class="text-muted ml-1">(atual)</small>
            @endif
          </button>
        </form>
      @endforeach
    </div>
  </div>
@else
  <span class="btn btn-sm {{ $project->status->color() }}">
    {{ $project->status->label() }}
  </span>
@endcan
