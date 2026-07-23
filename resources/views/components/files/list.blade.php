@props(['owner', 'files', 'sharedFiles' => null])

@php
  $uploadRoute = match ($owner::class) {
    \App\Models\Project::class => route('projects.files.store', $owner),
    \App\Models\Task::class => route('tasks.files.store', $owner),
    \App\Models\Meeting::class => route('meetings.files.store', $owner),
  };
@endphp

<section class="card mt-3" aria-labelledby="files-heading">
  <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2 py-2">
    <h2 id="files-heading" class="h6 m-0 text-muted mr-2">
      <i class="fas fa-paperclip mr-1" aria-hidden="true"></i> Arquivos
    </h2>

    @can('create', [\App\Models\Media::class, $owner])
      <form action="{{ $uploadRoute }}" method="post" enctype="multipart/form-data" class="form-inline" data-file-upload-form>
        @csrf
        <label class="sr-only" for="file-upload-{{ $owner->getMorphClass() }}-{{ $owner->id }}">Enviar Arquivo</label>
        <input id="file-upload-{{ $owner->getMorphClass() }}-{{ $owner->id }}" type="file" name="file" required class="form-control form-control-sm mr-2">
        <button class="btn btn-sm btn-primary" type="submit">Enviar Arquivo</button>
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
                <div class="d-flex flex-wrap gap-2">
                  <a class="btn btn-sm btn-outline-secondary" href="{{ route('files.download', ['uuid' => $media->uuid]) }}">Baixar</a>
                  @can('update', $media)
                    <form action="{{ route('files.update', ['uuid' => $media->uuid]) }}" method="post" class="form-inline">
                      @csrf
                      @method('PATCH')
                      <label class="sr-only" for="file-name-{{ $media->uuid }}">Nome exibido</label>
                      <input id="file-name-{{ $media->uuid }}" name="name" value="{{ $media->display_name }}" class="form-control form-control-sm" required maxlength="255">
                      <button type="submit" class="btn btn-sm btn-outline-primary ml-1">Renomear</button>
                    </form>
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
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('files.download', ['uuid' => $media->uuid]) }}">Baixar</a>
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
