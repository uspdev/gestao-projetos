@php
  $commentableType = $commentableType ?? $commentable->getMorphClass();
  $comments = $commentable->comments()->active()->with('user')->oldest()->get();
  $hasComments = $comments->isNotEmpty();
  $cardClass = $cardClass ?? '';
@endphp

<div id="comments-{{ $commentableType }}-{{ $commentable->getKey() }}" tabindex="-1" data-deep-link-target
  @class(['card', 'mb-4', 'shadow-sm', $cardClass])>
  <div class="card-header py-2 d-flex align-items-center">
    <h6 class="m-0 text-muted mr-2">
      <i class="fas fa-comments mr-1" aria-hidden="true"></i> Comentários
    </h6>
    <span class="badge badge-pill badge-secondary">{{ $comments->count() }}</span>
  </div>
  <div class="card-body py-3">
    @include('comments.partials.list')

    @include('comments.partials.form')
  </div>
</div>
