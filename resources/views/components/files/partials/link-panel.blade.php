<div id="{{ $componentId }}-links-panel" class="file-tab-panel {{ $initialTab !== 'links' ? 'd-none' : '' }}"
  role="tabpanel" aria-labelledby="{{ $componentId }}-links-tab" data-file-tab-panel="links">
  @if ($visibleLinks->isEmpty())
    <p class="text-muted small m-3">Nenhum Link disponível.</p>
  @else
    <div class="list-group list-group-flush file-compact-list">
      @foreach ($visibleLinks as $link)
        @php($isShared = $sharedLinkIds->contains($link->id))
        <article id="link-{{ $link->uuid }}"
          class="list-group-item file-list-item d-flex flex-wrap align-items-center py-2 pl-3 pr-2"
          data-link-card><a href="{{ $link->url }}" target="_blank" rel="noopener noreferrer"
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
          </a><x-links.actions :link="$link" :owner="$owner" :shared="$isShared" />
          <div class="file-item-edit-region w-100 mt-2 pt-2 border-top" data-file-edit-region hidden></div>
        </article>
      @endforeach
    </div>
  @endif
</div>
