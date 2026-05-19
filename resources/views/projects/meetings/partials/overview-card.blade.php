@php
  $linkedProjects = $meeting?->projects ?? collect();
@endphp

<div class="card mb-4 shadow-sm">
  <div class="card-header h5 d-flex align-items-center justify-content-between flex-wrap">
    <span><i class="far fa-calendar-alt mr-1"></i> {{ $meeting->title }}</span>

    <div class="d-flex align-items-center gap-2">
      <span class="badge {{ $meeting->status?->color() ?? 'badge-light text-dark' }}">
        {{ $meeting->status?->label() ?? '-' }}
      </span>

      @if ($showActions)
        @can('update', [$meeting, $project])
          <a href="{{ route('projects.meetings.edit', [$project, $meeting]) }}" class="btn btn-sm btn-outline-primary">
            <i class="fas fa-edit"></i>
          </a>
        @endcan

        @can('delete', [$meeting, $project])
          <form method="POST" action="{{ route('projects.meetings.destroy', [$project, $meeting]) }}"
            class="d-inline-block" onsubmit="return confirm('Deseja remover esta reuniao?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm">
              <i class="fas fa-trash"></i>
            </button>
          </form>
        @endcan
      @endif
    </div>
  </div>

  <div class="card-body">
    <div class="row">
      <div class="col-md-6">
        <div class="mb-3">
          <small class="text-muted d-block">Data e hora</small>
          <strong>
            <x-local-date :date="$meeting->scheduled_at" empty="-" />
          </strong>
        </div>

        <div class="mb-3 mb-md-0">
          <small class="text-muted d-block">Local</small>
          <strong>{{ $meeting->location ?? '-' }}</strong>
        </div>
      </div>

      <div class="col-md-6">
        <small class="text-muted d-block mb-2">Projetos vinculados</small>

        @if ($linkedProjects->isNotEmpty())
          <div class="d-flex flex-wrap" style="gap: 0.5rem;">
            @foreach ($linkedProjects as $linkedProject)
              <a href="{{ route('projects.show', $linkedProject) }}"
                class="badge badge-light border text-decoration-none">
                {{ $linkedProject->name }}
              </a>
            @endforeach
          </div>
        @else
          <div class="text-muted">Nenhum projeto vinculado.</div>
        @endif
      </div>
    </div>

    <hr>

    <div>
      <small class="text-muted d-block">Notas</small>
      @if ($meeting->notes)
        <div class="text-dark mt-2">
          <x-markdown-content :text="$meeting->notes" />
        </div>
      @else
        <div class="text-muted">Nenhuma nota registrada.</div>
      @endif
    </div>
  </div>
</div>
