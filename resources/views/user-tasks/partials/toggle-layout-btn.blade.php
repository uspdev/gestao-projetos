@php
  $href = $kanbanView ? request()->fullUrlWithoutQuery('view') : request()->fullUrlWithQuery(['view' => 'kanban']);
@endphp

<a href="{{ $href }}" class="btn btn-sm btn-outline-secondary"
  title="{{ $kanbanView ? 'Ver em lista' : 'Ver em kanban' }}">
  <i class="fas {{ $kanbanView ? 'fa-list' : 'fa-columns' }}"></i>
  <span class="ml-1">{{ $kanbanView ? 'Ver em lista' : 'Ver em kanban' }}</span>
</a>
