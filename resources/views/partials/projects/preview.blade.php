@php
  $user = auth()->user();
  $userRole = $user ? $project->userRole($user) : null;
@endphp

<x-card.preview class="mb-3 shadow-sm border-left-primary" href="{{ route('projects.show', $project->id) }}"
  aria-label="Acessar projeto {{ $project->name }}">
  <x-slot name="header">
    <div class="d-flex align-items-center w-100" style="gap:0.5rem;">
      <h5 class="m-0 pr-2 preview-card__title preview-card__title--project text-truncate"
        style="max-width: calc(100% - 110px);" title="{{ $project->name }}">
        {{ $project->name }}
      </h5>

      <span class="badge {{ $project->status->color() }} text-nowrap shadow-sm ml-auto" style="margin-top:0.2rem;">
        {{ $project->status->label() }}
      </span>
    </div>
  </x-slot>

  <x-slot name="body">
    <div class="preview-card__meta d-flex align-items-center" style="gap:0.5rem;">
      @auth
        <span class="text-muted mr-1 small"><i class="fas fa-user-circle"></i> Meu papel:</span>
        <span class="badge {{ $userRole?->color() ?? 'badge-light border text-muted' }} small text-truncate"
          style="max-width:9rem;">{{ $userRole?->label() ?? 'Sem vínculo' }}</span>
      @endauth
    </div>
  </x-slot>

  <x-slot name="footer">
    @php
      $allTags = $project->tagsWithType('projects');
      $visibleTags = $allTags->take(3);
      $extraCount = max(0, $allTags->count() - $visibleTags->count());
    @endphp

    <div class="d-flex align-items-center flex-wrap" style="gap: 0.25rem; max-height:2.6rem; overflow:hidden; font-size:0.9rem;">
      @foreach ($visibleTags as $tag)
        <span class="badge {{ $tag->color }} d-inline-flex align-items-center" title="Tag"
          style="font-size:0.85rem;">
          <i class="fas fa-tag mr-1"></i>
          <span class="d-inline-block text-truncate" style="max-width:7.5rem;">{{ $tag->name }}</span>
        </span>
      @endforeach

      @if ($extraCount > 0)
        <span class="badge badge-light border text-muted" title="+{{ $extraCount }} outras tags"
          style="font-size:0.85rem;">+{{ $extraCount }}</span>
      @endif
    </div>
  </x-slot>
</x-card.preview>
