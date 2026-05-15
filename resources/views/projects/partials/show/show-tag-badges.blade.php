<div class="d-flex flex-wrap align-items-center" style="gap: 0.35rem;">
  @foreach ($project->tags as $tag)
    <span class="badge badge-pill {{ $tag->color }}" title="{{ $tag->description }}">{{ $tag->name }}</span>
  @endforeach
</div>
