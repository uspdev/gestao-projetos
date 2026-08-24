@props([
  'media',
  'owner',
  'shared' => false,
  'suffix' => 'item',
])

@php
  $menuId = "file-actions-{$media->uuid}-{$suffix}";
  $renameId = "file-rename-{$media->uuid}-{$suffix}";
@endphp

<div class="dropdown flex-shrink-0" data-file-action>
  <button
    class="btn btn-sm btn-light text-secondary border-0 py-1 px-2"
    type="button"
    id="{{ $menuId }}"
    data-toggle="dropdown"
    aria-haspopup="true"
    aria-expanded="false"
    aria-label="Ações de {{ $media->display_name }}">
    <i class="fas fa-ellipsis-h" aria-hidden="true"></i>
  </button>
  <div class="dropdown-menu dropdown-menu-right small" aria-labelledby="{{ $menuId }}">
    @can('update', $media)
      <button
        type="button"
        class="dropdown-item"
        data-file-rename-toggle
        aria-controls="{{ $renameId }}"
        aria-expanded="false">
        <i class="fas fa-pen fa-fw mr-2" aria-hidden="true"></i>Renomear/editar
      </button>
    @endcan

    <a class="dropdown-item" href="{{ route('files.download', ['uuid' => $media->uuid]) }}">
      <i class="fas fa-download fa-fw mr-2" aria-hidden="true"></i>Baixar
    </a>

    @can('update', $media)
      <div class="dropdown-divider"></div>
      <form action="{{ route('files.destroy', ['uuid' => $media->uuid]) }}" method="post" onsubmit="return confirm('A exclusão é definitiva e referências existentes poderão deixar de funcionar.');">
        @csrf
        @method('DELETE')
        <button type="submit" class="dropdown-item text-danger">
          <i class="fas fa-trash-alt fa-fw mr-2" aria-hidden="true"></i>Excluir
        </button>
      </form>
    @endcan

    @if ($shared)
      @can('manageFileShares', $owner)
        <div class="dropdown-divider"></div>
        <form action="{{ route('meetings.file-shares.destroy', [$owner, $media->uuid]) }}" method="post">
          @csrf
          @method('DELETE')
          <button type="submit" class="dropdown-item text-danger">
            <i class="fas fa-unlink fa-fw mr-2" aria-hidden="true"></i>Remover da reunião
          </button>
        </form>
      @endcan
    @endif
  </div>

  @can('update', $media)
    <form
      id="{{ $renameId }}"
      action="{{ route('files.update', ['uuid' => $media->uuid]) }}"
      method="post"
      class="w-100 mb-0"
      data-file-rename-form
      data-disable-client-validation
      hidden>
      @csrf
      @method('PATCH')
      <label class="sr-only" for="{{ $renameId }}-name">Nome exibido</label>
      <div class="input-group input-group-sm">
        <input id="{{ $renameId }}-name" name="name" value="{{ $media->display_name }}" class="form-control form-control-sm" required maxlength="255" data-file-rename-input>
        <div class="input-group-append">
          <button type="submit" class="btn btn-primary" aria-label="Salvar" title="Salvar">
            <i class="fas fa-save" aria-hidden="true"></i><span class="sr-only">Salvar</span>
          </button>
          <button type="button" class="btn btn-outline-secondary" data-file-rename-cancel aria-label="Cancelar"
            title="Cancelar"><i class="fas fa-times" aria-hidden="true"></i><span class="sr-only">Cancelar</span></button>
        </div>
      </div>
    </form>
  @endcan
</div>
