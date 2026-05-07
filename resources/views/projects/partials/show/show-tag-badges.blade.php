<span class="border-primary">
  @foreach ($project->tags as $tag)
    <span class="badge badge-pill {{ $tag->color }}" title="{{ $tag->description }}">{{ $tag->name }}</span>
  @endforeach
</span>
