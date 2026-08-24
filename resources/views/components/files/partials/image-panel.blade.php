<div id="{{ $componentId }}-images-panel"
  class="file-tab-panel p-2 {{ $initialTab !== 'images' ? 'd-none' : '' }}" role="tabpanel"
  aria-labelledby="{{ $componentId }}-images-tab" data-file-tab-panel="images">
  @if ($imageFiles->isEmpty())
    <p class="text-muted small m-2">Nenhuma imagem com pré-visualização disponível.</p>
  @else
    <div class="row mx-n1">
      @foreach ($imageFiles as $media)
        @php($isShared = $sharedMediaIds->contains($media->id))
        <article id="{{ deep_link_fragment($media) }}" tabindex="-1" data-deep-link-target
          class="file-image-item col-6 col-sm-4 col-lg-3 px-1 mb-2"
          data-file-card data-file-uuid="{{ $media->uuid }}">
          <div class="file-image-card min-width-0 border rounded h-100 bg-white">
            @if ($media->getCustomProperty('thumbnail_status') === 'ready')
              <a href="{{ route('files.original', ['uuid' => $media->uuid]) }}" data-toggle="modal"
                data-target="#{{ $imageModalId }}" data-file-image-preview
                data-file-image-preview-url="{{ route('files.original', ['uuid' => $media->uuid]) }}"
                data-file-image-preview-name="{{ $media->display_name }}"
                class="file-image-select d-flex align-items-center justify-content-center overflow-hidden"
                aria-label="Abrir imagem original de {{ $media->display_name }}">
                <img src="{{ route('files.thumbnail', ['uuid' => $media->uuid]) }}" alt=""
                  class="file-image-thumbnail d-block w-100 h-100">
              </a>
            @else
              <div class="file-image-select d-flex align-items-center justify-content-center overflow-hidden"
                role="img" aria-label="Imagem original indisponível para visualização">
                <i class="far fa-image fa-2x text-muted" aria-hidden="true"></i>
              </div>
            @endif
            <div class="file-image-caption d-flex align-items-center min-width-0 pl-2"><span
                class="file-image-name min-width-0 flex-grow-1 text-truncate font-weight-bold"
                title="{{ $media->display_name }}">{{ $media->display_name }}</span><x-files.actions
                :media="$media" :owner="$owner" :shared="$isShared" /></div>
          </div>
          <div class="file-item-edit-region border rounded bg-light p-2 mt-1" data-file-edit-region hidden></div>
        </article>
      @endforeach
    </div>
  @endif
</div>

@if ($imageFiles->contains(fn($media) => $media->getCustomProperty('thumbnail_status') === 'ready'))
  @push('modals')
    <div class="modal fade file-image-preview-modal" id="{{ $imageModalId }}" tabindex="-1" role="dialog"
      aria-labelledby="{{ $imageModalId }}-title" aria-hidden="true" data-file-image-preview-modal>
      <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header py-2">
            <h2 class="modal-title h5 text-truncate" id="{{ $imageModalId }}-title" data-file-image-preview-title>
              Visualização da imagem
            </h2>
            <button type="button" class="close" data-dismiss="modal" aria-label="Fechar"><span
                aria-hidden="true">&times;</span></button>
          </div>
          <div class="modal-body text-center p-2">
            <img alt="" class="file-image-preview-original img-fluid mx-auto" data-file-image-preview-image hidden>
          </div>
        </div>
      </div>
    </div>
  @endpush
@endif
