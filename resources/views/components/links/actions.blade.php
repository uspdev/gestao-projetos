@props(['link', 'owner', 'shared' => false])

@php($formId = 'link-edit-' . $link->uuid)
<div class="dropdown flex-shrink-0 ml-2" data-file-action>
  <button class="btn btn-sm btn-light text-secondary border-0 py-1 px-2" type="button"
    id="link-actions-{{ $link->uuid }}" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"
    aria-label="Ações de {{ $link->display_name }}"><i class="fas fa-ellipsis-h" aria-hidden="true"></i></button>
  <div class="dropdown-menu dropdown-menu-right small" aria-labelledby="link-actions-{{ $link->uuid }}">
    @can('update', $link)
      <button type="button" class="dropdown-item" data-link-edit-toggle aria-controls="{{ $formId }}" aria-expanded="false"><i
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
      class="w-100 mb-0" data-link-edit-form hidden data-disable-client-validation>@csrf @method('PATCH')
      <div class="form-row">
        <div class="col-12 col-md-6 mb-2"><label class="small mb-1" for="{{ $formId }}-name">Nome do link</label><input
            id="{{ $formId }}-name" name="name" value="{{ $link->display_name }}" class="form-control form-control-sm"
            required maxlength="255"></div>
        <div class="col-12 col-md-6 mb-2"><label class="small mb-1" for="{{ $formId }}-url">URL</label><input
            id="{{ $formId }}-url" name="url" value="{{ $link->url }}" class="form-control form-control-sm" required
            maxlength="2048" type="url"></div>
        <div class="col-12 d-flex justify-content-end"><button type="button"
            class="btn btn-sm btn-outline-secondary mr-2" data-link-edit-cancel aria-label="Cancelar" title="Cancelar"><i
              class="fas fa-times" aria-hidden="true"></i><span class="sr-only">Cancelar</span></button><button
            type="submit" class="btn btn-sm btn-primary" aria-label="Salvar" title="Salvar"><i class="fas fa-save"
              aria-hidden="true"></i><span class="sr-only">Salvar</span></button>
        </div>
      </div>
    </form>
  @endcan
</div>
