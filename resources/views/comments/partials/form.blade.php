@can('comment', $commentable)
  <form method="POST" action="{{ route('comments.store') }}" class="mb-3">
    @csrf
    <input type="hidden" name="commentable_type" value="{{ $commentableType }}">
    <input type="hidden" name="commentable_id" value="{{ $commentable->getKey() }}">

    <div class="border rounded-lg bg-white p-2 shadow-sm">
      <x-form.textarea name="text" id="comment-text" groupClass="mb-0" value="{{ old('text') }}" rows="1"
        data-autogrow-textarea maxlength="10000" required placeholder="Escreva um comentário..."
        aria-label="Escreva um comentário" class="border-0 shadow-none p-0 mb-0" />

      <div class="d-flex justify-content-end mt-2">
        <button type="submit" class="btn btn-primary btn-sm">
          <i class="fas fa-paper-plane mr-1"></i>
        </button>
      </div>
    </div>
  </form>
@else
  <div class="text-muted small mb-3">Somente colaboradores podem comentar.</div>
@endcan
