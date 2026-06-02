@php
  $tags = $task->tags;
  $visible = $tags->slice(0, 5);
  $hidden = max(0, $tags->count() - $visible->count());
@endphp
<div class="d-flex flex-wrap" style="max-width:100%; gap:6px;">
  @forelse($visible as $tag)
    <span class="badge badge-{{ $tag->color }} small mb-1">
      <i class="fas fa-tag mr-1"></i>{{ \Illuminate\Support\Str::limit($tag->name, 18) }}
    </span>
  @empty
    <span class="text-muted font-italic small">-</span>
  @endforelse
  @if ($hidden > 0)
    <span class="badge bg-secondary small mb-1">+{{ $hidden }}</span>
  @endif
</div>
