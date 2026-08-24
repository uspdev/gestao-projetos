@if ($owner instanceof \App\Models\Meeting && ($shareableFileGroups->isNotEmpty() || $shareableLinkGroups->isNotEmpty()))
  @push('modals')
    <div class="modal fade" id="{{ $componentId }}-share-links-modal" tabindex="-1" role="dialog"
      aria-labelledby="{{ $componentId }}-share-links-title" aria-hidden="true">
      <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h2 class="modal-title h5" id="{{ $componentId }}-share-links-title">Compartilhar links e arquivos com a reunião</h2>
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
