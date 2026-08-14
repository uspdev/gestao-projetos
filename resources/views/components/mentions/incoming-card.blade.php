@props([
    'target',
    'cardTitle' => 'Menções',
    'emptyMessage' => 'Nenhuma menção encontrada.',
])

@php
  $mentions = app(\App\Services\Mentions\MentionBacklinks::class)->for($target, auth()->user());
@endphp

@pushOnce('styles')
  <style>
    .mentions-card__list {
      max-height: 22rem;
      overflow-y: auto;
    }
  </style>
@endPushOnce

<div class="card mb-4 shadow-sm" data-mentions-card="{{ $target->getMorphClass() }}-{{ $target->getKey() }}">
  <div class="card-header py-2 d-flex align-items-center justify-content-between">
    <h6 class="m-0 text-muted mr-2">
      <i class="fas fa-at mr-1" aria-hidden="true"></i> {{ $cardTitle }}
    </h6>
    <span class="badge badge-pill badge-secondary">{{ $mentions->count() }}</span>
  </div>

  <div class="card-body p-0">
    <div class="mentions-card__list" tabindex="0" aria-label="{{ $cardTitle }}">
      <div class="p-3">
        @forelse ($mentions as $mention)
          <div class="d-flex align-items-start justify-content-between @unless($loop->last) mb-3 @endunless"
            style="gap: 0.75rem;">
            <div style="min-width: 0;">
              <a href="{{ $mention['url'] }}" class="font-weight-bold text-dark text-break">
                {{ $mention['label'] }}
              </a>
              <div class="small text-muted">{{ $mention['type'] }}</div>
            </div>
            <span class="badge badge-light border text-muted text-nowrap">{{ $mention['field'] }}</span>
          </div>
        @empty
          <div class="text-muted">{{ $emptyMessage }}</div>
        @endforelse
      </div>
    </div>
  </div>
</div>
