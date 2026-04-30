@php
  $href = ($view === 'kanban') ? request()->fullUrlWithQuery(['view' => 'list']) : request()->fullUrlWithQuery(['view' => 'kanban']);
@endphp

@if ($view === 'kanban')
  <a href="{{ $href }}" class="btn btn-sm btn-outline-secondary py-0" title="Ver em lista">
    <i class="fas fa-list"></i>
    <span class="ml-1">Ver em lista</span>
  </a>
@else
  <a href="{{ $href }}" class="btn btn-sm btn-outline-secondary" title="Ver em kanban">
    <i class="fas fa-columns"></i>
    <span class="ml-1">Ver em kanban</span>
  </a>
@endif
