@if ($comments->isNotEmpty())
  <ul class="list-group list-group-flush mb-2">
    @foreach ($comments as $comment)
      @include('comments.partials.item', ['comment' => $comment])
    @endforeach
  </ul>
@endif
