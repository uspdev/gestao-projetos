@props([
    'href' => null,
    'ariaLabel' => null,
    'cardTitle' => null,
    'titleVariant' => 'project',
    'titleTag' => 'h5',
    'titleClass' => '',
    'statusLabel' => null,
    'statusClass' => 'badge-light border text-muted',
    'subprojectLabel' => null,
    'subprojectClass' => 'badge-light border text-muted',
    'actionClass' => '',
    'projectName' => null,
    'showProject' => true,
    'projectType' => null,
    'projectTypeIcon' => 'fa-sitemap',
    'roleLabel' => null,
    'roleClass' => 'badge-light border text-muted',
    'tags' => [],
    'tagsLimit' => 2,
    'footerPriorityLabel' => null,
    'footerPriorityClass' => null,
    'footerTags' => [],
    'footerTagsLimit' => 3,
    'startDate' => null,
    'dueDate' => null,
    'dueDateIsLate' => false,
])

@php
  $normalizedTags = collect($tags ?? []);
  $visibleTags = $normalizedTags->take($tagsLimit);
  $extraTagsCount = max(0, $normalizedTags->count() - $visibleTags->count());

  $normalizedFooterTags = collect($footerTags ?? []);
  $visibleFooterTags = $normalizedFooterTags->take($footerTagsLimit);
  $extraFooterTagsCount = max(0, $normalizedFooterTags->count() - $visibleFooterTags->count());
@endphp

@pushOnce('styles')
  <style>
    .preview-card {
      cursor: pointer;
      border-radius: 0.75rem;
      overflow: hidden;
      transition: transform 0.24s cubic-bezier(0.22, 1, 0.36, 1),
        box-shadow 0.24s cubic-bezier(0.22, 1, 0.36, 1);
    }

    .preview-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 0.85rem 1.7rem rgba(0, 0, 0, 0.16);
    }

    .preview-card__title {
      display: -webkit-box;
      -webkit-box-orient: vertical;
      overflow: hidden;
      word-break: break-word;
      font-weight: 800;
      line-height: 1.3;
      color: #1f2937;
    }

    .preview-card__title--project {
      font-size: 1.12rem;
      -webkit-line-clamp: 2;
    }

    .preview-card__title--task {
      font-size: 1.06rem;
      -webkit-line-clamp: 2;
    }

    .preview-card__title--feature {
      font-size: 1.1rem;
      -webkit-line-clamp: 2;
    }

    .preview-card__meta {
      font-size: 0.85rem;
    }

    .preview-card__content {
      min-height: 2.5rem;
    }

    .preview-card__project {
      font-size: 0.9rem;
      color: #6b7280;
    }

    .preview-card__feature-icon {
      font-size: 1.8rem;
      color: #004b87;
      margin-bottom: 1rem;
    }

    .preview-card__feature-desc {
      font-size: 0.95rem;
      color: #4a6072;
      line-height: 1.5;
      margin: 0;
    }

    .preview-card__row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      width: 100%;
      gap: 0.5rem;
    }

    .preview-card__role-tags {
      display: flex;
      align-items: center;
      justify-content: space-between;
      width: 100%;
      gap: 0.6rem;
      flex-wrap: nowrap;
    }

    .preview-card__role {
      display: inline-flex;
      align-items: center;
      min-width: 0;
      flex-shrink: 0;
      gap: 0.35rem;
    }

    .preview-card__tags {
      display: inline-flex;
      align-items: center;
      justify-content: flex-start;
      min-width: 0;
      overflow: hidden;
      gap: 0.25rem;
    }

    .preview-card__tags .badge {
      font-size: 0.78rem;
      padding: 0.29rem 0.42rem;
      max-width: 7rem;
    }

    .preview-card__action {
      opacity: 0;
      visibility: hidden;
      transform: translateY(-2px);
      pointer-events: none;
      transition: opacity 0.2s ease, transform 0.2s ease;
    }

    .preview-card:hover .preview-card__action,
    .preview-card:focus-within .preview-card__action {
      opacity: 1;
      visibility: visible;
      transform: translateY(0);
      pointer-events: auto;
    }
  </style>
@endPushOnce

<div {{ $attributes->class(['card preview-card position-relative h-100']) }}>
  <div class="card-body p-3 d-flex flex-column">
    @isset($header)
      <div class="d-flex justify-content-between align-items-start mb-1">
        {{ $header }}
      </div>
    @else
      @if ($cardTitle || $statusLabel)
        <div class="d-flex justify-content-between align-items-start mb-2">
          <div class="preview-card__row">
            <div class="d-flex align-items-center flex-wrap min-width-0" style="gap: 0.35rem;">
              <{{ $titleTag }}
                class="m-0 preview-card__title preview-card__title--{{ $titleVariant }} {{ $titleClass }}"
                title="{{ $cardTitle }}">
                {{ $cardTitle }}
              </{{ $titleTag }}>

              @if ($statusLabel)
                <span class="badge badge-{{ $statusClass }} text-nowrap shadow-sm">
                  {{ $statusLabel }}
                </span>
              @endif

              @if ($subprojectLabel)
                <span class="badge badge-{{ $subprojectClass }} text-nowrap shadow-sm">
                  {{ $subprojectLabel }}
                </span>
              @endif
            </div>

            @isset($action)
              <div class="{{ $actionClass }} ml-2">
                {{ $action }}
              </div>
            @endisset
          </div>
        </div>
      @endif
    @endisset

    @isset($body)
      <div>
        {{ $body }}
      </div>
    @else
      @if (($showProject && $projectName) || $roleLabel || $visibleTags->isNotEmpty() || $projectType)
        <div class="preview-card__content">
          @if ($showProject && $projectName)
            <div class="preview-card__project mb-2">
              <i class="fas fa-folder-open mr-1"></i>
              {{ $projectName }}
            </div>
          @endif
          @if ($projectType)
            <div class="d-flex justify-content-start w-100 mb-2">
              <small class="text-muted d-flex align-items-center">
                <i class="fas {{ $projectTypeIcon }} mr-1" title="Tipo de projeto"></i>
                <span>{{ $projectType }}</span>
              </small>
            </div>
          @endif

          <div class="preview-card__meta preview-card__role-tags">
            <div class="preview-card__role">
              @if ($roleLabel)
                <span class="text-muted small mb-0"><i class="fas fa-user-circle"></i> Meu papel:</span>
                <span class="badge {{ $roleClass }} small text-truncate"
                  style="max-width:8.5rem;">{{ $roleLabel }}</span>
              @endif
            </div>

            <div class="preview-card__tags" aria-label="Tags">
              @foreach ($visibleTags as $tag)
                <span
                  class="badge {{ data_get($tag, 'color', 'badge-light border text-muted') }} d-inline-flex align-items-center"
                  title="Tag {{ data_get($tag, 'name') }}">
                  <i class="fas fa-tag mr-1"></i>
                  <span class="d-inline-block text-truncate">{{ data_get($tag, 'name') }}</span>
                </span>
              @endforeach

              @if ($extraTagsCount > 0)
                <span class="badge badge-light border text-muted" title="+{{ $extraTagsCount }} outras tags">
                  +{{ $extraTagsCount }}
                </span>
              @endif
            </div>
          </div>
        </div>
      @endif
    @endisset

    {{ $slot }}

    @isset($footer)
      <div class="d-flex justify-content-between align-items-end mt-auto">
        {{ $footer }}
      </div>
    @else
      @if ($footerPriorityLabel || $visibleFooterTags->isNotEmpty() || !is_null($startDate) || !is_null($dueDate))
        <div class="d-flex justify-content-between align-items-end mt-auto">
          <div class="d-flex align-items-center flex-wrap" style="gap: 0.25rem; max-height:3.6rem; overflow:hidden;">
            @if ($footerPriorityLabel)
              <span
                class="badge {{ $footerPriorityClass ? 'badge-' . $footerPriorityClass : 'badge-light border text-muted' }}"
                title="Prioridade">
                <i class="fas fa-flag mr-1"></i>{{ $footerPriorityLabel }}
              </span>
            @endif

            @foreach ($visibleFooterTags as $tag)
              <span
                class="badge {{ data_get($tag, 'color', 'badge-light border text-muted') }} d-inline-flex align-items-center"
                title="Tag">
                <i class="fas fa-tag mr-1"></i>
                <span class="d-inline-block text-truncate" style="max-width:8rem;">{{ data_get($tag, 'name') }}</span>
              </span>
            @endforeach

            @if ($extraFooterTagsCount > 0)
              <span class="badge badge-light border text-muted" title="+{{ $extraFooterTagsCount }} outras tags">
                +{{ $extraFooterTagsCount }}
              </span>
            @endif
          </div>

          <div class="text-muted text-right text-nowrap pl-2" style="font-size: 0.85rem;">
            <i class="far fa-calendar-alt mr-1"></i>

            <span title="Data de Início">
              <x-local-date :date="$startDate" empty="--/--/----" />
            </span>

            <i class="fas fa-arrow-right mx-1" style="font-size: 0.7em; color: #adb5bd;"></i>

            <span title="Prazo de Entrega" class="font-weight-bold {{ $dueDateIsLate ? 'text-danger' : 'text-dark' }}">
              <x-local-date :date="$dueDate" :overdue="$dueDateIsLate" />
            </span>
          </div>
        </div>
      @endif
    @endisset

    @if ($href)
      <a href="{{ $href }}" class="stretched-link"
        @if ($ariaLabel) aria-label="{{ $ariaLabel }}" @endif></a>
    @endif
  </div>
</div>
