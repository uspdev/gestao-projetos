<div id="{{ $componentId }}-documents-panel"
  class="file-tab-panel {{ $initialTab !== 'documents' ? 'd-none' : '' }}" role="tabpanel"
  aria-labelledby="{{ $componentId }}-documents-tab" data-file-tab-panel="documents">
  @if ($documentFiles->isEmpty())
    <p class="text-muted small m-3">Nenhum Documento disponível.</p>
  @else
    <div class="list-group list-group-flush file-compact-list">
      @foreach ($documentFiles as $media)
        @php($isShared = $sharedMediaIds->contains($media->id))
        <article id="{{ deep_link_fragment($media) }}" tabindex="-1" data-deep-link-target
          class="list-group-item list-group-item-action file-list-item d-flex flex-wrap align-items-center py-2 pl-3 pr-2"
          data-file-card data-file-uuid="{{ $media->uuid }}"><a
            href="{{ route('files.download', ['uuid' => $media->uuid]) }}"
            class="d-flex align-items-center min-width-0 flex-grow-1 text-body text-decoration-none"><i
              class="{{ $documentIcon($media) }} text-secondary mr-2" aria-hidden="true"></i><span
              class="file-list-name small min-width-0 flex-grow-1 font-weight-bold text-truncate"
              title="{{ $media->display_name }}">{{ $media->display_name }}</span>
            @if ($isShared)
              <span class="badge badge-light mr-2">Compartilhado</span>
            @endif
          </a>
          <x-files.actions :media="$media" :owner="$owner" :shared="$isShared" />
          <div class="file-item-edit-region w-100 mt-2 pt-2 border-top" data-file-edit-region hidden></div>
        </article>
      @endforeach
    </div>
  @endif
</div>
