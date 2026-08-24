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

    .meetings-list {
      max-height: min(70vh, 42rem);
      overflow-y: auto;
      overflow-x: hidden;
      overscroll-behavior-y: auto;
      padding-right: .25rem;
    }

    .meetings-list--dashboard {
      max-height: 22rem;
    }
  </style>
@endPushOnce

<div id="{{ deep_link_fragment($meeting) }}" class="card meeting-card entity-card entity-card--meeting"
  tabindex="-1" data-deep-link-target>
  <div class="card-body py-2 px-3">
    <a href="{{ route('projects.meetings.show', [$project, $meeting]) }}"
      class="stretched-link"></a>
    @if ($compact)
      <div class="d-flex align-items-start gap-2 mb-2">
        <div class="d-flex align-items-center flex-wrap flex-grow-1 min-width-0" style="gap: 0.35rem;">
          <div class="text-dark font-weight-bold text-truncate">{{ $meeting->title }}</div>
          <div class="small text-muted"><i class="fas fa-folder-open"></i> {{ $project->name }}</div>
          <span class="badge badge-{{ $meeting->status?->color() }}">{{ $meeting->status?->label() }}</span>
        </div>

        @if ($showDuplicate)
          <div class="d-flex align-items-center position-relative" style="z-index: 2;">
              @include('module-meetings.partials.duplicate-btn')
          </div>
        @endif
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
      <div class="d-flex align-items-start gap-2 mb-2">
        <div class="d-flex align-items-center flex-wrap flex-grow-1 min-width-0" style="gap: 0.35rem;">
          <div class="text-dark font-weight-bold text-truncate">{{ $meeting->title }}</div>
          <span class="badge badge-{{ $meeting->status?->color() }}">{{ $meeting->status?->label() }}</span>
        </div>

        @if ($showDuplicate)
          <div class="d-flex align-items-center position-relative" style="z-index: 2;">
              @include('module-meetings.partials.duplicate-btn')
          </div>
        @endif
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
