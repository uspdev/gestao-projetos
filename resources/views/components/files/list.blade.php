@props(['owner', 'files', 'sharedFiles' => null])

@php
  $uploadRoute = match ($owner::class) {
    \App\Models\Project::class => route('projects.files.store', $owner),
    \App\Models\Task::class => route('tasks.files.store', $owner),
    \App\Models\Meeting::class => route('meetings.files.store', $owner),
  };
  $componentId = 'files-'.$owner->getMorphClass().'-'.$owner->id;
  $ownedVisibleFiles = $files->getCollection()
    ->filter(fn ($media) => \Illuminate\Support\Facades\Gate::allows('view', $media));
  $sharedVisibleFiles = collect($sharedFiles ?? [])
    ->filter(fn ($media) => \Illuminate\Support\Facades\Gate::allows('view', $media));
  $sharedMediaIds = $sharedVisibleFiles->pluck('id');
  $visibleFiles = $ownedVisibleFiles
    ->concat($sharedVisibleFiles)
    ->unique('id')
    ->values();
  $rasterMimeTypes = [
    'image/jpeg',
    'image/png',
    'image/gif',
    'image/webp',
    'image/avif',
  ];
  $imageFiles = $visibleFiles
    ->filter(fn ($media) => in_array($media->mime_type, $rasterMimeTypes, true));
  $otherFiles = $visibleFiles
    ->reject(fn ($media) => in_array($media->mime_type, $rasterMimeTypes, true));
  $initialTab = $imageFiles->isNotEmpty() ? 'images' : 'files';
@endphp

<section class="card my-4 file-browser" aria-labelledby="{{ $componentId }}-heading" data-file-browser>
  <div class="card-header d-flex flex-column flex-sm-row align-items-start py-2">
    <h2 id="{{ $componentId }}-heading" class="h6 mt-1 mr-sm-3 mb-2 mb-sm-0 text-muted text-nowrap">
      <i class="fas fa-paperclip mr-1" aria-hidden="true"></i> Arquivos
    </h2>

    @can('create', [\App\Models\Media::class, $owner])
      <form action="{{ $uploadRoute }}" method="post" enctype="multipart/form-data" class="d-flex flex-column flex-grow-1 w-100 mb-0 min-width-0" data-file-upload-form data-disable-client-validation>
        @csrf
        <div class="d-flex align-items-center">
          <input id="file-upload-{{ $owner->getMorphClass() }}-{{ $owner->id }}" type="file" name="file" required class="sr-only" data-file-upload-input>
          <label class="btn btn-sm btn-outline-secondary mb-0 mr-2" for="file-upload-{{ $owner->getMorphClass() }}-{{ $owner->id }}">Procurar</label>
          <button class="btn btn-sm btn-primary" type="submit" data-file-upload-submit disabled aria-disabled="true">Enviar Arquivo</button>
        </div>
        <span class="d-none mt-1 small text-success text-truncate min-width-0" data-file-upload-feedback aria-live="polite">
          <button class="btn btn-sm btn-link p-0 mr-1 text-danger" type="button" data-file-upload-clear aria-label="Remover arquivo selecionado">
            <i class="fas fa-times" aria-hidden="true"></i>
          </button>
          <i class="fas fa-check-circle mr-1" aria-hidden="true"></i><span data-file-upload-name></span>
        </span>
        @if ($errors->has('file'))
          <div class="invalid-feedback d-block mt-1" role="alert">{{ $errors->first('file') }}</div>
        @endif
      </form>
    @endcan
  </div>

  <div class="card-body p-0">
    @if ($visibleFiles->isEmpty())
      <p class="text-muted m-3">Nenhum Arquivo disponível.</p>
    @else
      <ul class="nav nav-tabs bg-white px-3 pt-2 small" role="tablist" aria-label="Visualização dos Arquivos">
        <li class="nav-item">
          <button
            id="{{ $componentId }}-images-tab"
            class="nav-link {{ $initialTab === 'images' ? 'active' : '' }}"
            type="button"
            role="tab"
            aria-selected="{{ $initialTab === 'images' ? 'true' : 'false' }}"
            aria-controls="{{ $componentId }}-images-panel"
            tabindex="{{ $initialTab === 'images' ? '0' : '-1' }}"
            data-file-tab="images">
            <i class="far fa-images mr-1" aria-hidden="true"></i>
            Imagens <span class="badge badge-light ml-1">{{ $imageFiles->count() }}</span>
          </button>
        </li>
        <li class="nav-item">
          <button
            id="{{ $componentId }}-files-tab"
            class="nav-link {{ $initialTab === 'files' ? 'active' : '' }}"
            type="button"
            role="tab"
            aria-selected="{{ $initialTab === 'files' ? 'true' : 'false' }}"
            aria-controls="{{ $componentId }}-files-panel"
            tabindex="{{ $initialTab === 'files' ? '0' : '-1' }}"
            data-file-tab="files">
            <i class="far fa-file mr-1" aria-hidden="true"></i>
            Arquivos <span class="badge badge-light ml-1">{{ $otherFiles->count() }}</span>
          </button>
        </li>
      </ul>

      <div class="file-browser-scroll" data-file-browser-scroll>
        <div
          id="{{ $componentId }}-images-panel"
          class="file-tab-panel p-2 {{ $initialTab !== 'images' ? 'd-none' : '' }}"
          role="tabpanel"
          aria-labelledby="{{ $componentId }}-images-tab"
          data-file-tab-panel="images">
          @if ($imageFiles->isEmpty())
            <p class="text-muted small m-2">Nenhuma imagem com pré-visualização disponível.</p>
          @else
            <div class="row mx-n1">
              @foreach ($imageFiles as $media)
                @php
                  $isShared = $sharedMediaIds->contains($media->id);
                  $isPreviewable = $media->getCustomProperty('thumbnail_status') === 'ready';
                @endphp
                <article
                  id="file-{{ $media->uuid }}"
                  class="file-image-item col-6 col-sm-4 col-lg-3 px-1 mb-2"
                  data-file-card
                  data-file-uuid="{{ $media->uuid }}"
                  @if ($isShared) data-file-shared-with-meeting @endif>
                  <div class="file-image-card min-width-0 border rounded h-100 bg-white">
                    <button
                      type="button"
                      class="file-image-select btn btn-light d-flex align-items-center justify-content-center w-100 p-0 overflow-hidden text-body border-0"
                      aria-label="Abrir pré-visualização e metadados de {{ $media->display_name }}"
                      data-file-select
                      data-file-details-id="{{ $componentId }}-details-{{ $media->uuid }}">
                      @if ($isPreviewable)
                        <img
                          src="{{ route('files.thumbnail', ['uuid' => $media->uuid]) }}"
                          alt=""
                          class="file-image-thumbnail d-block w-100 h-100">
                      @else
                        <span class="d-flex flex-column align-items-center justify-content-center p-2 text-center text-muted">
                          <i class="far fa-image fa-2x mb-1" aria-hidden="true"></i>
                          <small>
                            Prévia indisponível
                          </small>
                        </span>
                      @endif
                    </button>
                    <div class="file-image-caption d-flex align-items-center min-width-0 pl-2">
                      <button
                        type="button"
                        class="file-image-name btn btn-link btn-sm min-width-0 flex-grow-1 p-0 text-left text-truncate font-weight-bold"
                        title="{{ $media->display_name }}"
                        data-file-select
                        data-file-details-id="{{ $componentId }}-details-{{ $media->uuid }}">
                        {{ $media->display_name }}
                      </button>
                      <x-files.actions :media="$media" :owner="$owner" :shared="$isShared" :previewable="$isPreviewable" :details-id="$componentId.'-details-'.$media->uuid" />
                    </div>
                  </div>
                </article>
              @endforeach
            </div>
          @endif
        </div>

        <div
          id="{{ $componentId }}-files-panel"
          class="file-tab-panel {{ $initialTab !== 'files' ? 'd-none' : '' }}"
          role="tabpanel"
          aria-labelledby="{{ $componentId }}-files-tab"
          data-file-tab-panel="files">
          @if ($otherFiles->isEmpty())
            <p class="text-muted small m-3">Nenhum outro Arquivo disponível.</p>
          @else
            <div class="list-group list-group-flush file-compact-list">
              @foreach ($otherFiles as $media)
                @php
                  $isShared = $sharedMediaIds->contains($media->id);
                @endphp
                <article
                  id="file-{{ $media->uuid }}"
                  class="list-group-item list-group-item-action file-list-item d-flex align-items-center py-2 pl-3 pr-2"
                  data-file-card
                  data-file-uuid="{{ $media->uuid }}"
                  @if ($isShared) data-file-shared-with-meeting @endif>
                  <button
                    type="button"
                    class="file-list-select btn btn-link d-flex align-items-center min-width-0 flex-grow-1 p-0 text-left"
                    aria-label="Abrir metadados e ações de {{ $media->display_name }}"
                    data-file-select
                    data-file-details-id="{{ $componentId }}-details-{{ $media->uuid }}">
                    <i class="far fa-file text-secondary mr-2" aria-hidden="true"></i>
                    <span class="file-list-name small min-width-0 flex-grow-1 font-weight-bold text-truncate" title="{{ $media->display_name }}">{{ $media->display_name }}</span>
                    @if ($isShared)
                      <span class="badge badge-light mr-2">Compartilhado</span>
                    @endif
                  </button>
                  <x-files.actions :media="$media" :owner="$owner" :shared="$isShared" :previewable="false" :details-id="$componentId.'-details-'.$media->uuid" />
                </article>
              @endforeach
            </div>
          @endif
        </div>
      </div>

      <div
        class="file-rename-region border-top bg-light p-3"
        data-file-rename-region
        aria-label="Editar nome do Arquivo"
        hidden>
      </div>

      <div class="file-details-region border-top bg-light" aria-live="polite">
        <p class="text-muted small m-3" data-file-details-placeholder>
          Selecione um item para ver a pré-visualização, os metadados e as ações disponíveis.
        </p>

        @foreach ($visibleFiles as $media)
          @php
            $isPreviewable = $media->getCustomProperty('thumbnail_status') === 'ready';
            $isShared = $sharedMediaIds->contains($media->id);
            $thumbnailLabel = match ($media->getCustomProperty('thumbnail_status')) {
              'ready' => 'Disponível',
              'not_supported' => 'Não disponível',
              default => 'Não disponível',
            };
          @endphp
          <article
            id="{{ $componentId }}-details-{{ $media->uuid }}"
            class="file-details p-3"
            tabindex="-1"
            data-file-details
            hidden>
            <div class="d-block d-sm-flex align-items-start">
              @if ($isPreviewable)
                <a
                  href="{{ route('files.thumbnail', ['uuid' => $media->uuid]) }}"
                  target="_blank"
                  rel="noopener"
                  class="file-details-preview d-flex align-items-center justify-content-center flex-shrink-0 overflow-hidden bg-white border rounded mr-sm-3 mb-3 mb-sm-0"
                  aria-label="Abrir pré-visualização de {{ $media->display_name }}">
                  <img class="d-block w-100 h-100" src="{{ route('files.thumbnail', ['uuid' => $media->uuid]) }}" alt="Pré-visualização de {{ $media->display_name }}">
                </a>
              @else
                <div class="file-details-icon d-flex align-items-center justify-content-center flex-shrink-0 overflow-hidden bg-white border rounded mr-sm-3 mb-3 mb-sm-0" aria-hidden="true">
                  <i class="far fa-file fa-2x text-secondary"></i>
                </div>
              @endif

              <div class="min-width-0 flex-grow-1">
                <div class="d-flex align-items-start justify-content-between">
                  <h3 class="h6 text-truncate mb-1" title="{{ $media->display_name }}">{{ $media->display_name }}</h3>
                  <x-files.actions :media="$media" :owner="$owner" :shared="$isShared" :previewable="$isPreviewable" :details-id="$componentId.'-details-'.$media->uuid" suffix="details" />
                </div>
                <dl class="row mx-n2 small text-muted mb-0">
                  <div class="col-12 col-sm-6 d-flex min-width-0 px-2"><dt class="mr-1 text-secondary">Tipo</dt><dd class="min-width-0 mb-0">{{ strtoupper(pathinfo($media->file_name, PATHINFO_EXTENSION)) ?: 'Sem extensão' }}</dd></div>
                  <div class="col-12 col-sm-6 d-flex min-width-0 px-2"><dt class="mr-1 text-secondary">Tamanho</dt><dd class="min-width-0 mb-0">{{ number_format($media->size / 1024, 1, ',', '.') }} KB</dd></div>
                  <div class="col-12 col-sm-6 d-flex min-width-0 px-2"><dt class="mr-1 text-secondary">MIME</dt><dd class="min-width-0 mb-0 text-break">{{ $media->mime_type ?: 'Não identificado' }}</dd></div>
                  <div class="col-12 col-sm-6 d-flex min-width-0 px-2"><dt class="mr-1 text-secondary">Miniatura</dt><dd class="min-width-0 mb-0">{{ $thumbnailLabel }}</dd></div>
                  <div class="col-12 col-sm-6 d-flex min-width-0 px-2"><dt class="mr-1 text-secondary">Enviado por</dt><dd class="min-width-0 mb-0">{{ $media->uploader?->name ?? 'Usuário removido' }}</dd></div>
                  <div class="col-12 col-sm-6 d-flex min-width-0 px-2"><dt class="mr-1 text-secondary">Data</dt><dd class="min-width-0 mb-0">{{ optional($media->created_at)->format('d/m/Y H:i') }}</dd></div>
                  @can('viewOriginal', $media)
                    <div class="col-12 d-flex min-width-0 px-2"><dt class="mr-1 text-secondary">Nome original</dt><dd class="min-width-0 mb-0 text-break">{{ $media->original_name }}</dd></div>
                  @endcan
                  @if ($isShared)
                    <div class="col-12 d-flex min-width-0 px-2"><dt class="mr-1 text-secondary">Acesso</dt><dd class="min-width-0 mb-0">Compartilhado com a reunião</dd></div>
                  @endif
                </dl>
              </div>
            </div>
          </article>
        @endforeach
      </div>

      <div class="px-3 pt-2">{{ $files->links() }}</div>
    @endif
  </div>
</section>

@once
  @include('components.files.inline-actions')
@endonce
