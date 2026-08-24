<ul class="nav nav-tabs bg-white px-3 pt-2 small" role="tablist" aria-label="Visualização dos recursos">
  @foreach ([['images', 'far fa-images', 'Imagens', $imageFiles->count()], ['documents', 'far fa-file', 'Documentos', $documentFiles->count()], ['links', 'fas fa-link', 'Links', $visibleLinks->count()]] as [$tab, $icon, $label, $count])
    @php
      $tabFragment = match ($tab) {
          'images' => 'medias-imagens',
          'documents' => 'medias-documentos',
          'links' => 'medias-links',
      };
    @endphp
    <li class="nav-item"><button id="{{ $componentId }}-{{ $tab }}-tab"
        class="nav-link {{ $initialTab === $tab ? 'active' : '' }}" type="button" role="tab"
        aria-selected="{{ $initialTab === $tab ? 'true' : 'false' }}"
        aria-controls="{{ $componentId }}-{{ $tab }}-panel"
        tabindex="{{ $initialTab === $tab ? '0' : '-1' }}" data-file-tab="{{ $tab }}"
        data-file-tab-fragment="{{ $tabFragment }}"><i
          class="{{ $icon }} mr-1" aria-hidden="true"></i>{{ $label }} <span
          class="badge badge-light ml-1">{{ $count }}</span></button></li>
  @endforeach
</ul>
