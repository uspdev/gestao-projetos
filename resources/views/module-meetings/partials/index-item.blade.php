@props([
    'compact' => false,
])

@php
  $statusClass = $meeting->status?->color() ?? 'light';
@endphp

<a href="{{ route('projects.meetings.show', [$project, $meeting]) }}"
  class="card h-100 shadow-sm text-decoration-none border-0 cursor-pointer">

  @if ($compact)
    <div class="card-body py-2 px-3">
      <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
        <div class="text-dark font-weight-bold text-truncate">
          {{ $meeting->title }}
          <div class="small text-muted">
            <i class="fas fa-folder-open"></i> {{ $project->name }}
            </div>
        </div>

        <span class="badge badge-{{ $statusClass }}">
          {{ $meeting->status?->label() ?? '-' }}
        </span>
      </div>
      <div class="d-flex align-items-start gap-2">
        <div class="small text-muted mb-1">
          <i class="ti ti-calendar-event mr-1"></i>
          <x-local-date :date="$meeting->scheduled_at" empty="sem data" />
        </div>

        <div class="small text-muted text-truncate">
          <i class="ti ti-map-pin mr-1"></i>
          {{ $meeting->location ?? 'Sem local' }}
        </div>
      </div>
    </div>
  @else
    <div class="card-body d-flex flex-column">
      <div class="d-flex align-items-start justify-content-between gap-2 mb-3">
        <div>
          <div class="font-weight-bold text-dark">{{ $meeting->title }}</div>
          <small class="text-muted">
            <x-local-date :date="$meeting->scheduled_at" empty="-" />
          </small>
        </div>

        <span class="badge badge-{{ $statusClass }}">
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
  @endif
</a>
