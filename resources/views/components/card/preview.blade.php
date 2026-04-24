@props([
    'href' => null,
    'ariaLabel' => null,
])

@once
  <style>
    .preview-card {
      cursor: pointer;
      border-radius: 0.9rem;
      overflow: hidden;
      transition: transform 0.24s cubic-bezier(0.22, 1, 0.36, 1),
        box-shadow 0.24s cubic-bezier(0.22, 1, 0.36, 1);
    }

    .preview-card:hover {
      transform: translateY(-6px) scale(1.01);
      box-shadow: 0 1.1rem 2.2rem rgba(0, 0, 0, 0.2);
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
      font-size: 1.25rem;
      -webkit-line-clamp: 2;
      min-height: 3.25rem;
    }

    .preview-card__title--task {
      font-size: 1.12rem;
      -webkit-line-clamp: 2;
      min-height: 2.91rem;
    }

    .preview-card__title--feature {
      font-size: 1.15rem;
      -webkit-line-clamp: 2;
      min-height: 2.99rem;
    }

    .preview-card__meta {
      font-size: 0.85rem;
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
  </style>
@endonce

<div {{ $attributes->class(['card preview-card position-relative']) }}>
  <div class="card-body p-3 d-flex flex-column h-100">
    @isset($header)
      <div class="d-flex justify-content-between align-items-start mb-3">
        {{ $header }}
      </div>
    @endisset

    @isset($body)
      <div class="mt-2">
        {{ $body }}
      </div>
    @endisset

    {{ $slot }}

    @isset($footer)
      <div class="d-flex justify-content-between align-items-end mt-auto">
        {{ $footer }}
      </div>
    @endisset

    @if ($href)
      <a href="{{ $href }}" class="stretched-link"
        @if ($ariaLabel) aria-label="{{ $ariaLabel }}" @endif></a>
    @endif
  </div>
</div>
