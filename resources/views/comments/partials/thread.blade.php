@php
  $commentableType = $commentableType ?? $commentable->getMorphClass();
  $comments = $commentable->comments()->active()->with('user')->oldest()->get();
@endphp

<div class="card mb-4 shadow-sm">
  <div class="card-header h5 d-flex align-items-center justify-content-between">
    <span><i class="far fa-comment-dots mr-1"></i> Comentários</span>
    <span class="badge badge-secondary">{{ $comments->count() }}</span>
  </div>
  <div class="card-body">
    <ul class="list-group list-group-flush mb-3">
      @foreach ($comments as $comment)
        <li class="list-group-item px-0">
          <div class="d-flex align-items-start justify-content-between gap-2">
            @include('users.partials.preview', ['user' => $comment->user])
            <div class="d-flex align-items-center gap-2">
              <small class="text-muted">
                <x-local-datetime :date="$comment->created_at" />
              </small>
              @can('delete', $comment)
                <form method="POST" action="{{ route('comments.destroy', $comment) }}" class="d-inline-block"
                  onsubmit="return confirm('Deseja realmente apagar este comentário?');">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-outline-danger btn-sm py-0">
                    <i class="fas fa-trash"></i>
                  </button>
                </form>
              @endcan
            </div>
          </div>
          <div class="mt-2 text-dark">
            <x-markdown-content :text="$comment->text" />
          </div>
        </li>
      @endforeach
    </ul>

    @can('comment', $commentable)
      <form method="POST" action="{{ route('comments.store') }}">
        @csrf
        <input type="hidden" name="commentable_type" value="{{ $commentableType }}">
        <input type="hidden" name="commentable_id" value="{{ $commentable->getKey() }}">
        <x-form.textarea name="text" label="Escreva um comentário:" rows="3" maxlength="10000" required />
        <div class="d-flex justify-content-end">
          <button type="submit" class="btn btn-primary btn-sm">
            <i class="fas fa-paper-plane mr-1"></i>
          </button>
        </div>
      </form>
    @else
      <div class="text-muted small">Somente colaboradores podem comentar.</div>
    @endcan
  </div>
</div>