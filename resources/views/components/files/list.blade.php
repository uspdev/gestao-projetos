@props(['owner', 'files', 'links', 'sharedFiles' => null, 'sharedLinks' => null])

@include('components.files.files-css')

@php
  $uploadRoute = match ($owner::class) {
      \App\Models\Project::class => route('projects.files.store', $owner),
      \App\Models\Task::class => route('tasks.files.store', $owner),
      \App\Models\Meeting::class => route('meetings.files.store', $owner),
  };
  $linkRoute = match ($owner::class) {
      \App\Models\Project::class => route('projects.links.store', $owner),
      \App\Models\Task::class => route('tasks.links.store', $owner),
      \App\Models\Meeting::class => route('meetings.links.store', $owner),
  };
  $componentId = 'files-' . $owner->getMorphClass() . '-' . $owner->id;
  $ownedVisibleFiles = $files
      ->getCollection()
      ->filter(fn($media) => \Illuminate\Support\Facades\Gate::allows('view', $media));
  $sharedVisibleFiles = collect($sharedFiles ?? [])->filter(
      fn($media) => \Illuminate\Support\Facades\Gate::allows('view', $media),
  );
  $sharedMediaIds = $sharedVisibleFiles->pluck('id');
  $visibleFiles = $ownedVisibleFiles->concat($sharedVisibleFiles)->unique('id')->values();
  $ownedVisibleLinks = $links
      ->getCollection()
      ->filter(fn($link) => \Illuminate\Support\Facades\Gate::allows('view', $link));
  $sharedVisibleLinks = collect($sharedLinks ?? [])->filter(
      fn($link) => \Illuminate\Support\Facades\Gate::allows('view', $link),
  );
  $sharedLinkIds = $sharedVisibleLinks->pluck('id');
  $visibleLinks = $ownedVisibleLinks->concat($sharedVisibleLinks)->unique('id')->values();
  $rasterMimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/avif'];
  $imageFiles = $visibleFiles->filter(fn($media) => in_array($media->mime_type, $rasterMimeTypes, true));
  $documentFiles = $visibleFiles->reject(fn($media) => in_array($media->mime_type, $rasterMimeTypes, true));
  $initialTab = $imageFiles->isNotEmpty()
      ? 'images'
      : ($documentFiles->isNotEmpty()
          ? 'documents'
          : ($visibleLinks->isNotEmpty()
              ? 'links'
              : 'images'));
  $linkModalId = $componentId . '-add-links-modal';
  $shareableFileGroups = collect();
  $shareableLinkGroups = collect();
  $canManageFileShares =
      $owner instanceof \App\Models\Meeting && \Illuminate\Support\Facades\Gate::allows('manageFileShares', $owner);
  $canManageLinkShares =
      $owner instanceof \App\Models\Meeting &&
      \Illuminate\Support\Facades\Schema::hasTable('links') &&
      \Illuminate\Support\Facades\Gate::allows('manageLinkShares', $owner);
  if ($canManageFileShares || $canManageLinkShares) {
      $sharedMediaIds = $owner->sharedFiles()->pluck('media.id');
      $sharedIds = $sharedVisibleLinks->pluck('id');
      $linkedProjects = $owner->projects()->get();
      $agendaOwners = $owner->meetingItems()->with('discussable')->get()->pluck('discussable');
      // Monta as possíveis fontes de links e Arquivos compartilháveis:
      // 1. projetos vinculados diretamente à reunião;
      // 2. projetos presentes na pauta e ainda não listados acima;
      // 3. tarefas presentes na pauta.
      $sources = $linkedProjects
          ->map(fn($project) => ['label' => "Projeto vinculado: {$project->name}", 'owner' => $project])
          ->concat(
              $agendaOwners
                  ->filter(fn($item) => $item instanceof \App\Models\Project)
                  ->reject(fn($project) => $linkedProjects->contains('id', $project->id))
                  ->unique('id')
                  ->map(fn($project) => ['label' => "Projeto na pauta: {$project->name}", 'owner' => $project]),
          )
          ->concat(
              $agendaOwners
                  ->filter(fn($item) => $item instanceof \App\Models\Task)
                  ->unique('id')
                  ->map(fn($task) => ['label' => "Tarefa na pauta: {$task->title}", 'owner' => $task]),
          );

      if ($canManageFileShares) {
          $shareableFileGroups = $sources
              ->map(function (array $source) use ($sharedMediaIds) {
                  $available = $source['owner']
                      ->media()
                      ->with('uploader')
                      ->latest()
                      ->get()
                      ->reject(fn($media) => $sharedMediaIds->contains($media->id))
                      ->filter(fn($media) => \Illuminate\Support\Facades\Gate::allows('view', $media))
                      ->values();
                  return ['label' => $source['label'], 'files' => $available];
              })
              ->filter(fn(array $group) => $group['files']->isNotEmpty())
              ->values();
      }

      if ($canManageLinkShares) {
          // Para cada projeto ou tarefa encontrado, busca os links que ainda podem
          // ser compartilhados com a reunião.
          $shareableLinkGroups = $sources
              ->map(function (array $source) use ($sharedIds) {
                  $available = $source['owner']
                      ->links()
                      ->with('creator')
                      ->latest()
                      ->get()
                      ->reject(fn($link) => $sharedIds->contains($link->id))
                      ->filter(fn($link) => \Illuminate\Support\Facades\Gate::allows('view', $link))
                      ->values();
                  return ['label' => $source['label'], 'links' => $available];
              })
              ->filter(fn(array $group) => $group['links']->isNotEmpty())
              ->values();
      }
  }

  // Retorna a classe de ícone Font Awesome adequada à extensão do arquivo.
  // Caso a extensão não esteja mapeada, usa um ícone de arquivo genérico.
  $documentIcon = function ($media): string {
      return match (strtolower(pathinfo($media->file_name, PATHINFO_EXTENSION))) {
          'pdf' => 'far fa-file-pdf',
          'doc', 'docx', 'odt', 'rtf' => 'far fa-file-word',
          'xls', 'xlsx', 'ods', 'csv' => 'far fa-file-excel',
          'ppt', 'pptx', 'odp' => 'far fa-file-powerpoint',
          'txt', 'md', 'log' => 'far fa-file-alt',
          'zip', 'rar', '7z', 'tar', 'gz' => 'far fa-file-archive',
          default => 'far fa-file',
      };
  };
@endphp

<section class="card my-4 file-browser" aria-labelledby="{{ $componentId }}-heading" data-file-browser>
  <div class="card-header d-flex flex-column flex-sm-row align-items-start py-2">
    <h2 id="{{ $componentId }}-heading" class="h6 mt-1 mr-sm-3 mb-2 mb-sm-0 text-muted text-nowrap"><i
        class="fas fa-paperclip mr-1" aria-hidden="true"></i> Arquivos</h2>
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
            aria-hidden="true"></i>Compartilhar midia</button>
      @endif
    </div>
  </div>

  <div class="card-body p-0">
    <ul class="nav nav-tabs bg-white px-3 pt-2 small" role="tablist" aria-label="Visualização dos recursos">
      @foreach ([['images', 'far fa-images', 'Imagens', $imageFiles->count()], ['documents', 'far fa-file', 'Documentos', $documentFiles->count()], ['links', 'fas fa-link', 'Links', $visibleLinks->count()]] as [$tab, $icon, $label, $count])
        <li class="nav-item"><button id="{{ $componentId }}-{{ $tab }}-tab"
            class="nav-link {{ $initialTab === $tab ? 'active' : '' }}" type="button" role="tab"
            aria-selected="{{ $initialTab === $tab ? 'true' : 'false' }}"
            aria-controls="{{ $componentId }}-{{ $tab }}-panel"
            tabindex="{{ $initialTab === $tab ? '0' : '-1' }}" data-file-tab="{{ $tab }}"><i
              class="{{ $icon }} mr-1" aria-hidden="true"></i>{{ $label }} <span
              class="badge badge-light ml-1">{{ $count }}</span></button></li>
      @endforeach
    </ul>

    <div class="file-browser-scroll" data-file-browser-scroll>
      <div id="{{ $componentId }}-images-panel"
        class="file-tab-panel p-2 {{ $initialTab !== 'images' ? 'd-none' : '' }}" role="tabpanel"
        aria-labelledby="{{ $componentId }}-images-tab" data-file-tab-panel="images">
        @if ($imageFiles->isEmpty())
          <p class="text-muted small m-2">Nenhuma imagem com pré-visualização disponível.</p>
        @else
          <div class="row mx-n1">
            @foreach ($imageFiles as $media)
              @php($isShared = $sharedMediaIds->contains($media->id))
              <article id="file-{{ $media->uuid }}" class="file-image-item col-6 col-sm-4 col-lg-3 px-1 mb-2"
                data-file-card data-file-uuid="{{ $media->uuid }}">
                <div class="file-image-card min-width-0 border rounded h-100 bg-white">
                  <a href="{{ route('files.thumbnail', ['uuid' => $media->uuid]) }}" target="_blank" rel="noopener"
                    class="file-image-select d-flex align-items-center justify-content-center overflow-hidden"
                    aria-label="Abrir pré-visualização de {{ $media->display_name }}">
                    @if ($media->getCustomProperty('thumbnail_status') === 'ready')
                      <img src="{{ route('files.thumbnail', ['uuid' => $media->uuid]) }}" alt=""
                      class="file-image-thumbnail d-block w-100 h-100">@else<i class="far fa-image fa-2x text-muted"
                        aria-hidden="true"></i>
                    @endif
                  </a>
                  <div class="file-image-caption d-flex align-items-center min-width-0 pl-2"><a
                      href="{{ route('files.download', ['uuid' => $media->uuid]) }}"
                      class="file-image-name min-width-0 flex-grow-1 p-0 text-left text-truncate font-weight-bold"
                      title="{{ $media->display_name }}">{{ $media->display_name }}</a><x-files.actions
                      :media="$media" :owner="$owner" :shared="$isShared" /></div>
                </div>
              </article>
            @endforeach
          </div>
        @endif
      </div>

      <div id="{{ $componentId }}-documents-panel"
        class="file-tab-panel {{ $initialTab !== 'documents' ? 'd-none' : '' }}" role="tabpanel"
        aria-labelledby="{{ $componentId }}-documents-tab" data-file-tab-panel="documents">
        @if ($documentFiles->isEmpty())
          <p class="text-muted small m-3">Nenhum Documento disponível.</p>
        @else
          <div class="list-group list-group-flush file-compact-list">
            @foreach ($documentFiles as $media)
              @php($isShared = $sharedMediaIds->contains($media->id))
              <article id="file-{{ $media->uuid }}"
                class="list-group-item list-group-item-action file-list-item d-flex align-items-center py-2 pl-3 pr-2"
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
              </article>
            @endforeach
          </div>
        @endif
      </div>

      <div id="{{ $componentId }}-links-panel" class="file-tab-panel {{ $initialTab !== 'links' ? 'd-none' : '' }}"
        role="tabpanel" aria-labelledby="{{ $componentId }}-links-tab" data-file-tab-panel="links">
        @if ($visibleLinks->isEmpty())
          <p class="text-muted small m-3">Nenhum Link disponível.</p>
        @else
          <div class="list-group list-group-flush file-compact-list">
            @foreach ($visibleLinks as $link)
              @php($isShared = $sharedLinkIds->contains($link->id))
              <article id="link-{{ $link->uuid }}"
                class="list-group-item file-list-item d-flex align-items-center py-2 pl-3 pr-2" data-link-card><a
                  href="{{ $link->url }}" target="_blank" rel="noopener noreferrer"
                  class="d-block min-width-0 flex-grow-1 text-body text-decoration-none">
                  <div class="d-flex align-items-center min-width-0"><i class="fas fa-link text-secondary mr-2"
                      aria-hidden="true"></i><span class="small font-weight-bold text-truncate"
                      title="{{ $link->display_name }}">{{ $link->display_name }}</span>
                    @if ($isShared)
                      <span class="badge badge-light ml-2">Compartilhado</span>
                    @endif
                  </div>
                  <small class="d-block text-muted text-truncate ml-4"
                    title="{{ $link->url }}">{{ $link->url }}</small>
                </a><x-links.actions :link="$link" :owner="$owner" :shared="$isShared" /></article>
            @endforeach
          </div>
        @endif
      </div>
    </div>
    <div class="file-rename-region border-top bg-light p-3" data-file-rename-region hidden></div>
    @if ($files->hasPages() || $links->hasPages())
      <div class="px-3 pt-2">{{ $files->links() }}{{ $links->links() }}</div>
    @endif
  </div>
</section>

@can('create', [\App\Models\Link::class, $owner])
  @push('modals')
    <div class="modal fade" id="{{ $linkModalId }}" tabindex="-1" role="dialog"
      aria-labelledby="{{ $linkModalId }}-title" aria-hidden="true">
      <div class="modal-dialog" role="document">
        <div class="modal-content">
          <form action="{{ $linkRoute }}" method="post" data-disable-client-validation>@csrf<div
              class="modal-header">
              <h2 class="modal-title h5" id="{{ $linkModalId }}-title">Adicionar links</h2><button type="button"
                class="close" data-dismiss="modal" aria-label="Fechar"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body"><label for="{{ $componentId }}-urls">Cole uma URL por linha</label>
              <textarea id="{{ $componentId }}-urls" name="urls" class="form-control @error('urls') is-invalid @enderror"
                rows="7" required placeholder="https://exemplo.org/documento&#10;https://exemplo.org/site">{{ old('urls') }}</textarea>
              @error('urls')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-outline-secondary"
                data-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Adicionar
                links</button></div>
          </form>
        </div>
      </div>
    </div>
  @endpush
  @if ($errors->has('urls'))
    @push('scripts')
      <script>
        document.addEventListener('DOMContentLoaded', function() {
          if (window.jQuery) window.jQuery('#{{ $linkModalId }}').modal('show');
        });
      </script>
    @endpush
  @endif
@endcan

@if ($owner instanceof \App\Models\Meeting && ($shareableFileGroups->isNotEmpty() || $shareableLinkGroups->isNotEmpty()))
  @push('modals')
    <div class="modal fade" id="{{ $componentId }}-share-links-modal" tabindex="-1" role="dialog"
      aria-labelledby="{{ $componentId }}-share-links-title" aria-hidden="true">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h2 class="modal-title h5" id="{{ $componentId }}-share-links-title">Compartilhar arquivos e links com a
              reunião</h2>
            <button type="button" class="close" data-dismiss="modal" aria-label="Fechar"><span
                aria-hidden="true">&times;</span></button>
          </div>
          <div class="modal-body">
            @if ($shareableFileGroups->isNotEmpty())
              <h3 class="h6">Arquivos</h3>
              @foreach ($shareableFileGroups as $group)
                <h4 class="small font-weight-bold">{{ $group['label'] }}</h4>
                <div class="list-group mb-3">
                  @foreach ($group['files'] as $media)
                    <form action="{{ route('meetings.file-shares.store', $owner) }}" method="post"
                      class="list-group-item d-flex align-items-center justify-content-between">@csrf<input
                        type="hidden" name="media_uuid" value="{{ $media->uuid }}"><span
                        class="text-truncate mr-3">{{ $media->display_name }}</span><button
                        class="btn btn-sm btn-primary" type="submit">Compartilhar</button></form>
                  @endforeach
                </div>
              @endforeach
            @endif

            @if ($shareableLinkGroups->isNotEmpty())
              <h3 class="h6">Links</h3>
              @foreach ($shareableLinkGroups as $group)
                <h4 class="small font-weight-bold">{{ $group['label'] }}</h4>
                <div class="list-group mb-3">
                  @foreach ($group['links'] as $link)
                    <form action="{{ route('meetings.link-shares.store', $owner) }}" method="post"
                      class="list-group-item d-flex align-items-center justify-content-between">@csrf<input
                        type="hidden" name="link_uuid" value="{{ $link->uuid }}"><span
                        class="text-truncate mr-3">{{ $link->display_name }}</span><button
                        class="btn btn-sm btn-primary" type="submit">Compartilhar</button></form>
                  @endforeach
                </div>
              @endforeach
            @endif
          </div>
        </div>
      </div>
    </div>
  @endpush
@endif

@once
  @include('components.files.inline-actions')
@endonce
