@php
  $commentableType =
      $commentableType ??
      ($commentable instanceof \App\Models\Project
          ? 'project'
          : ($commentable instanceof \App\Models\Task
              ? 'task'
              : get_class($commentable)));

  $comments = $commentable->comments()->with('user')->oldest()->get();
@endphp

<div class="card mb-4 shadow-sm">
  <div class="card-header h5 d-flex align-items-center justify-content-between">
    <span><i class="far fa-comment-dots mr-1"></i> Comentarios</span>
    <span class="badge badge-secondary">{{ $comments->count() }}</span>
  </div>
  <div class="card-body">
    @if ($comments->isEmpty())
      <div class="alert alert-light border text-muted mb-3">Nenhum comentario ainda.</div>
    @else
      <ul class="list-group list-group-flush mb-3">
        @foreach ($comments as $comment)
          <li class="list-group-item px-0">
            <div class="d-flex align-items-start justify-content-between gap-2">
              @include('users.partials.preview', ['user' => $comment->user])
              <small class="text-muted">
                <x-local-date :date="$comment->created_at" />
              </small>
            </div>
            <div class="mt-2 text-dark">
              {!! linkify(nl2br(e($comment->text))) !!}
            </div>
          </li>
        @endforeach
      </ul>
    @endif

    @can('comment', $commentable)
      <form method="POST" action="{{ route('comments.store') }}">
        @csrf
        <input type="hidden" name="commentable_type" value="{{ $commentableType }}">
        <input type="hidden" name="commentable_id" value="{{ $commentable->getKey() }}">
        <x-form.textarea name="text" label="Novo comentario" rows="3" maxlength="10000" required />
        <div class="d-flex justify-content-end">
          <button type="submit" class="btn btn-primary btn-sm">
            <i class="fas fa-paper-plane mr-1"></i> Adicionar comentario
          </button>
        </div>
      </form>
    @else
      <div class="text-muted small">Somente colaboradores podem comentar.</div>
    @endcan
  </div>
</div>
