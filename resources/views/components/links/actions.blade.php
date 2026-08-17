@props(['link', 'owner', 'shared' => false])

@php($formId = 'link-edit-' . $link->uuid)
<div class="dropdown flex-shrink-0 ml-2" data-file-action>
  <button class="btn btn-sm btn-light text-secondary border-0 py-1 px-2" type="button"
    id="link-actions-{{ $link->uuid }}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
    aria-label="Ações de {{ $link->display_name }}"><i class="fas fa-ellipsis-h" aria-hidden="true"></i></button>
  <div class="dropdown-menu dropdown-menu-right small" aria-labelledby="link-actions-{{ $link->uuid }}">
    @can('update', $link)
      <button type="button" class="dropdown-item" data-link-edit-toggle aria-controls="{{ $formId }}"><i
          class="fas fa-pen fa-fw mr-2" aria-hidden="true"></i>Editar</button>
    @endcan
    <a class="dropdown-item" href="{{ $link->url }}" target="_blank" rel="noopener noreferrer"><i
        class="fas fa-external-link-alt fa-fw mr-2" aria-hidden="true"></i>Abrir</a>
    @can('delete', $link)
      <div class="dropdown-divider"></div>
      <form action="{{ route('links.destroy', $link->uuid) }}" method="post"
        onsubmit="return confirm('A exclusão é definitiva.');">@csrf @method('DELETE')<button type="submit"
          class="dropdown-item text-danger"><i class="fas fa-trash-alt fa-fw mr-2" aria-hidden="true"></i>Excluir</button>
      </form>
    @endcan
    @if (
        $shared &&
            $owner instanceof \App\Models\Meeting &&
            \Illuminate\Support\Facades\Gate::allows('manageLinkShares', $owner))
      <div class="dropdown-divider"></div>
      <form action="{{ route('meetings.link-shares.destroy', [$owner, $link->uuid]) }}" method="post">@csrf
        @method('DELETE')<button type="submit" class="dropdown-item text-danger"><i class="fas fa-unlink fa-fw mr-2"
            aria-hidden="true"></i>Remover da reunião</button></form>
    @endif
  </div>
  @can('update', $link)
    <form id="{{ $formId }}" action="{{ route('links.update', $link->uuid) }}" method="post"
      class="border-top mt-2 pt-2" data-link-edit-form hidden data-disable-client-validation>@csrf @method('PATCH')<label
        class="sr-only" for="{{ $formId }}-name">Rótulo</label><input id="{{ $formId }}-name" name="name"
        value="{{ $link->display_name }}" class="form-control form-control-sm mb-2" required maxlength="255"><label
        class="sr-only" for="{{ $formId }}-url">URL</label><input id="{{ $formId }}-url" name="url"
        value="{{ $link->url }}" class="form-control form-control-sm mb-2" required maxlength="2048" type="url">
      <div class="d-flex justify-content-end"><button type="button" class="btn btn-sm btn-outline-secondary mr-2"
          data-link-edit-cancel>Cancelar</button><button type="submit" class="btn btn-sm btn-primary">Salvar</button>
      </div>
    </form>
  @endcan
</div>
