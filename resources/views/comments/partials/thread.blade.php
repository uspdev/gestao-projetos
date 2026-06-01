@php
  $commentableType = $commentableType ?? $commentable->getMorphClass();
  $comments = $commentable->comments()->active()->with('user')->oldest()->get();
  $hasComments = $comments->isNotEmpty();
@endphp

<div class="card mb-4 shadow-sm">
  <div class="card-header py-2 h5 d-flex align-items-center justify-content-between" style="background-color: lightCyan;">
    <span>Comentários</span>
    <span class="badge badge-pill badge-secondary">{{ $comments->count() }}</span>
  </div>
  <div class="card-body py-3">
    @include('comments.partials.list')

    @include('comments.partials.form')
  </div>
</div>
