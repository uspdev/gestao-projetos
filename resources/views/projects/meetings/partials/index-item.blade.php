@php
  $statusClass = $meeting->status?->color() ?? 'badge-light text-dark';
@endphp

<a href="{{ route('projects.meetings.show', [$project, $meeting]) }}"
  class="card h-100 shadow-sm text-decoration-none border-0">
  <div class="card-body d-flex flex-column">
    <div class="d-flex align-items-start justify-content-between gap-2 mb-3">
      <div>
        <div class="font-weight-bold text-dark">{{ $meeting->title }}</div>
        <small class="text-muted">
          <x-local-date :date="$meeting->scheduled_at" empty="-" />
        </small>
      </div>

      <span class="badge {{ $statusClass }}">
        {{ $meeting->status?->label() ?? '-' }}
      </span>
    </div>

    <div class="text-muted mb-3">
      {{ $meeting->location ?? 'Sem local informado' }}
    </div>

    <div class="mt-auto d-flex align-items-center justify-content-between text-muted small">
      <span>{{ $meeting->projects->count() }} projeto(s) vinculado(s)</span>
      <span>Ver detalhes</span>
    </div>
  </div>
</a>
