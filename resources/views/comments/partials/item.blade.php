<li class="list-group-item px-0 py-3">
  <div class="d-flex align-items-start justify-content-between">
    @include('users.partials.preview', ['user' => $comment->user])
    <div class="d-flex align-items-center ml-3 flex-shrink-0">
      <small class="text-muted mr-2 text-nowrap">
        <x-local-datetime :date="$comment->created_at" />
      </small>
      @include('comments.partials.delete-btn')
    </div>
  </div>
  <div class="mt-2 text-dark">
    <x-markdown-content :text="$comment->text" />
  </div>
</li>
