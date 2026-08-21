@php
  $commentEditDisplayId = 'comment-display-' . $comment->id;
  $commentEditFormId = 'comment-edit-' . $comment->id;
  $isEditingComment = (string) old('comment_id') === (string) $comment->id;
  $editTextValue = $isEditingComment ? old('text') : $comment->text;
@endphp

<li id="{{ deep_link_fragment($comment) }}" class="list-group-item px-0 py-3"
  tabindex="-1" data-deep-link-target>
  <div class="d-flex align-items-start justify-content-between">
    @include('users.partials.preview', ['user' => $comment->user])
    <div class="d-flex align-items-center ml-3 flex-shrink-0">
      <small class="text-muted mr-2 text-nowrap">
        <x-local-datetime :date="$comment->created_at" />
      </small>
      @can('update', $comment)
        <button type="button" class="btn btn-outline-secondary btn-sm py-0 mr-1" data-toggle="collapse"
          data-target="#{{ $commentEditDisplayId }}, #{{ $commentEditFormId }}" aria-label="Editar comentario">
          <i class="fas fa-edit"></i>
        </button>
      @endcan
      @include('comments.partials.delete-btn')
    </div>
  </div>
  <div class="mt-2 text-dark collapse {{ $isEditingComment ? '' : 'show' }}" id="{{ $commentEditDisplayId }}">
    <x-markdown.markdown-content :text="$comment->text" />
  </div>
  @can('update', $comment)
    <div class="collapse {{ $isEditingComment ? 'show' : '' }}" id="{{ $commentEditFormId }}">
      <form method="POST" action="{{ route('comments.update', $comment) }}" class="mt-2">
        @csrf
        @method('PATCH')
        <input type="hidden" name="comment_id" value="{{ $comment->id }}">

        <label for="{{ $commentEditFormId }}-textarea" class="sr-only">Editar comentario</label>
        <x-form.textarea name="text" :id="$commentEditFormId . '-textarea'" :value="$editTextValue" :error-bag="$isEditingComment ? $errors : new \Illuminate\Support\ViewErrorBag()" groupClass="form-group mb-2" markdown-profile="compact"
          rows="2" maxlength="10000" required
          data-file-reference-url="{{ route('files.selectable', ['context_type' => 'comment', 'commentable_type' => $comment->commentable->getMorphClass(), 'commentable_id' => $comment->commentable->getKey()]) }}"
          data-mention-search-url="{{ route('mentions.selectable', ['context_type' => 'comment', 'commentable_type' => $comment->commentable->getMorphClass(), 'commentable_id' => $comment->commentable->getKey()]) }}" />

        <div class="d-flex justify-content-end" style="gap: 0.5rem;">
          <x-form.cancel-button class="btn-sm" data-toggle="collapse"
            data-target="#{{ $commentEditDisplayId }}, #{{ $commentEditFormId }}" />
          <x-form.save-button class="btn btn-primary btn-sm" />
        </div>
      </form>
    </div>
  @endcan
</li>
