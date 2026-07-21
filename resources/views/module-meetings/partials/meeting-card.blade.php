@props([
    'compact' => false,
    'showDuplicate' => true,
])

@pushOnce('styles')
  <style>
    .meeting-card {
      border-radius: .75rem;
      transition: transform .2s ease, box-shadow .2s ease;
    }

    .meeting-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 .5rem 1rem rgba(0, 0, 0, .15) !important;
    }
  </style>
@endPushOnce

<div class="card meeting-card">
  <div class="card-body py-2 px-3">
    <a href="{{ route('projects.meetings.show', [$project, $meeting]) }}" class="stretched-link"></a>
    @if ($compact)
      <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
        <div>
          <div class="text-dark font-weight-bold text-truncate">{{ $meeting->title }}</div>
          <div class="small text-muted"><i class="fas fa-folder-open"></i> {{ $project->name }}</div>
        </div>

        <div class="d-flex align-items-center position-relative" style="z-index: 2;">
          <span class="badge badge-{{ $meeting->status?->color() }}">{{ $meeting->status?->label() }}</span>
          @if ($showDuplicate)
            <div class="ml-1">
              @include('module-meetings.partials.duplicate-btn')
            </div>
          @endif
        </div>
      </div>

      <div class="d-flex align-items-start gap-2">
        <div class="small text-muted mb-1">
          <i class="ti ti-calendar-event mr-1"></i>
          <x-local-date :date="$meeting->scheduled_at" empty="sem data" showTime="true" />
        </div>

        <div class="small text-muted text-truncate">
          <i class="ti ti-map-pin mr-1"></i> {{ $meeting->location ?? 'Sem local' }}
        </div>
      </div>
    @else
      <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
        <div>
          <div class="text-dark font-weight-bold text-truncate">{{ $meeting->title }}</div>
        </div>

        <div class="d-flex align-items-center position-relative" style="z-index: 2;">
          <span class="badge badge-{{ $meeting->status?->color() }}">{{ $meeting->status?->label() }}</span>
          @if ($showDuplicate)
            <div class="ml-1">
              @include('module-meetings.partials.duplicate-btn')
            </div>
          @endif
        </div>
      </div>

      <div class="d-flex align-items-start gap-2">
        <div class="small text-muted mb-1">
          <i class="ti ti-calendar-event mr-1"></i>
          <x-local-date :date="$meeting->scheduled_at" empty="sem data" showTime="true" />
        </div>

        <div class="small text-muted text-truncate">
          <i class="ti ti-map-pin mr-1"></i> {{ $meeting->location ?? 'Sem local' }}
        </div>
      </div>

      <div class="text-muted mb-3">Itens de pauta: {{ $meeting->meetingItems->count() }}</div>

      <div class="mt-auto d-flex align-items-center justify-content-between text-muted small">
        <span>Projetos vinculados: {{ $meeting->projects->pluck('name')->join(', ') }}</span>
      </div>
    @endif
  </div>
</div>
