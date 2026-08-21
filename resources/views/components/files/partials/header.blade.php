<div class="card-header d-flex flex-column flex-sm-row align-items-start py-2">
  <h2 id="{{ $componentId }}-heading" class="h6 mt-1 mr-sm-3 mb-2 mb-sm-0 text-muted text-nowrap"><i
      class="fas fa-paperclip mr-1" aria-hidden="true"></i> Mídias </h2>
  <div class="d-flex flex-wrap align-items-center">
    @can('create', [\App\Models\Media::class, $owner])
      <form action="{{ $uploadRoute }}" method="post" enctype="multipart/form-data" class="mb-0 mr-2"
        data-file-upload-form>@csrf
        <input id="file-upload-{{ $owner->getMorphClass() }}-{{ $owner->id }}" type="file" name="files[]" multiple
          class="sr-only" data-file-upload-input>
        <label class="btn btn-sm btn-primary mb-0"
          for="file-upload-{{ $owner->getMorphClass() }}-{{ $owner->id }}"><i class="fas fa-upload mr-1"
            aria-hidden="true"></i>Enviar arquivos</label>
        <span class="sr-only" aria-live="polite" data-file-upload-feedback></span><noscript><button
            class="btn btn-sm btn-outline-secondary" type="submit">Confirmar envio</button></noscript>
      </form>
    @endcan
    @can('create', [\App\Models\Link::class, $owner])
      <button type="button" class="btn btn-sm btn-outline-secondary mb-0 mr-2" data-toggle="modal"
        data-target="#{{ $linkModalId }}"><i class="fas fa-link mr-1" aria-hidden="true"></i>Adicionar links</button>
    @endcan
    @if ($owner instanceof \App\Models\Meeting && ($shareableFileGroups->isNotEmpty() || $shareableLinkGroups->isNotEmpty()))
      <button type="button" class="btn btn-sm btn-outline-secondary mb-0" data-toggle="modal"
        data-target="#{{ $componentId }}-share-links-modal"><i class="fas fa-share-alt mr-1"
          aria-hidden="true"></i>Compartilhar links e arquivos</button>
    @endif
  </div>
</div>
