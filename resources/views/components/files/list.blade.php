@props(['owner', 'files', 'sharedFiles' => null])

@php
  $uploadRoute = match ($owner::class) {
    \App\Models\Project::class => route('projects.files.store', $owner),
    \App\Models\Task::class => route('tasks.files.store', $owner),
    \App\Models\Meeting::class => route('meetings.files.store', $owner),
  };
@endphp

<section class="card my-4" aria-labelledby="files-heading">
  <div class="card-header d-flex align-items-start py-2">
    <h2 id="files-heading" class="h6 m-0 mt-1 mr-3 text-muted text-nowrap">
      <i class="fas fa-paperclip mr-1" aria-hidden="true"></i> Arquivos
    </h2>

    @can('create', [\App\Models\Media::class, $owner])
      <form action="{{ $uploadRoute }}" method="post" enctype="multipart/form-data" class="d-flex flex-column flex-grow-1 mb-0 file-upload-form" data-file-upload-form data-disable-client-validation>
        @csrf
        <div class="d-flex align-items-center">
          <input id="file-upload-{{ $owner->getMorphClass() }}-{{ $owner->id }}" type="file" name="file" required class="sr-only" data-file-upload-input>
          <label class="btn btn-sm btn-outline-secondary mb-0 mr-2" for="file-upload-{{ $owner->getMorphClass() }}-{{ $owner->id }}">Procurar</label>
          <button class="btn btn-sm btn-primary" type="submit" data-file-upload-submit disabled aria-disabled="true">Enviar Arquivo</button>
        </div>
        <span class="d-none mt-1 small text-success text-truncate file-upload-feedback" data-file-upload-feedback aria-live="polite">
          <button class="btn btn-sm btn-link p-0 mr-1 text-danger" type="button" data-file-upload-clear aria-label="Remover arquivo selecionado">
            <i class="fas fa-times" aria-hidden="true"></i>
          </button>
          <i class="fas fa-check-circle mr-1" aria-hidden="true"></i><span data-file-upload-name></span>
        </span>
      </form>
    @endcan
  </div>

  <div class="card-body">
    @if ($files->isEmpty() && ($sharedFiles === null || $sharedFiles->isEmpty()))
      <p class="text-muted mb-0">Nenhum Arquivo disponível.</p>
    @else
      <div class="row">
        @foreach ($files as $media)
          @can('view', $media)
            <article class="col-12 mb-2" data-file-card data-file-uuid="{{ $media->uuid }}">
              <div class="border rounded p-2 d-flex flex-wrap align-items-center gap-2">
                <div class="text-center" style="width: 64px">
                  @if ($media->getCustomProperty('thumbnail_status') === 'ready')
                    <img src="{{ route('files.thumbnail', ['uuid' => $media->uuid]) }}" alt="Miniatura de {{ $media->display_name }}" class="img-fluid rounded" style="max-height: 56px">
                  @else
                    <i class="far fa-file fa-2x text-secondary" aria-hidden="true"></i>
                  @endif
                </div>
                <div class="flex-grow-1 min-width-0">
                  <a href="{{ route('files.download', ['uuid' => $media->uuid]) }}" class="font-weight-bold">{{ $media->display_name }}</a>
                  <div class="small text-muted">
                    {{ strtoupper(pathinfo($media->file_name, PATHINFO_EXTENSION)) ?: 'SEM EXTENSÃO' }} ·
                    {{ number_format($media->size / 1024, 1, ',', '.') }} KB ·
                    {{ $media->uploader?->name ?? 'Usuário removido' }} ·
                    {{ optional($media->created_at)->format('d/m/Y H:i') }}
                  </div>
                  <div class="small text-muted">Miniatura: {{ $media->getCustomProperty('thumbnail_status') ?? 'pending' }}</div>
                  @can('viewOriginal', $media)
                    <div class="small text-muted">Nome original: {{ $media->original_name }}</div>
                  @endcan
                </div>
                <div class="d-flex align-items-center flex-nowrap gap-2">
                  @can('update', $media)
                    <div class="d-flex align-items-center flex-nowrap">
                      <button type="button" class="btn btn-sm btn-outline-primary" data-file-rename-toggle aria-controls="file-rename-{{ $media->uuid }}" aria-expanded="false">Renomear</button>
                      <form id="file-rename-{{ $media->uuid }}" action="{{ route('files.update', ['uuid' => $media->uuid]) }}" method="post" class="input-group input-group-sm" data-file-rename-form data-disable-client-validation hidden>
                        @csrf
                        @method('PATCH')
                        <label class="sr-only" for="file-name-{{ $media->uuid }}">Nome exibido</label>
                        <input id="file-name-{{ $media->uuid }}" name="name" value="{{ $media->display_name }}" class="form-control form-control-sm" required maxlength="255" data-file-rename-input>
                        <div class="input-group-append">
                          <button type="submit" class="btn btn-outline-primary" aria-label="Confirmar novo nome">OK</button>
                        </div>
                      </form>
                    </div>
                    <form action="{{ route('files.destroy', ['uuid' => $media->uuid]) }}" method="post" onsubmit="return confirm('A exclusão é definitiva e referências existentes poderão deixar de funcionar.');">
                      @csrf
                      @method('DELETE')
                      <button type="submit" class="btn btn-sm btn-outline-danger">Excluir</button>
                    </form>
                  @endcan
                </div>
              </div>
            </article>
          @endcan
        @endforeach
      </div>

      @if ($sharedFiles?->isNotEmpty())
        <h3 class="h6 mt-3">Compartilhados com a reunião</h3>
        <div class="row">
          @foreach ($sharedFiles as $media)
            @can('view', $media)
              <article class="col-12 mb-2" data-file-card data-file-uuid="{{ $media->uuid }}" data-file-shared-with-meeting>
                <div class="border rounded p-2 d-flex flex-wrap align-items-center gap-2">
                  <div class="text-center" style="width: 64px">
                    @if ($media->getCustomProperty('thumbnail_status') === 'ready')
                      <img src="{{ route('files.thumbnail', ['uuid' => $media->uuid]) }}" alt="Miniatura de {{ $media->display_name }}" class="img-fluid rounded" style="max-height: 56px">
                    @else
                      <i class="far fa-file fa-2x text-secondary" aria-hidden="true"></i>
                    @endif
                  </div>
                  <div class="flex-grow-1 min-width-0">
                    <a href="{{ route('files.download', ['uuid' => $media->uuid]) }}" class="font-weight-bold">{{ $media->display_name }}</a>
                    <div class="small text-muted">Arquivo compartilhado com a reunião · {{ $media->uploader?->name ?? 'Usuário removido' }}</div>
                  </div>
                  <div class="d-flex flex-wrap gap-2">
                    @can('manageFileShares', $owner)
                      <form action="{{ route('meetings.file-shares.destroy', [$owner, $media->uuid]) }}" method="post">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-outline-danger">Remover da reunião</button>
                      </form>
                    @endcan
                  </div>
                </div>
              </article>
            @endcan
          @endforeach
        </div>
      @endif

      <div class="mt-2">{{ $files->links() }}</div>
    @endif
  </div>
</section>
