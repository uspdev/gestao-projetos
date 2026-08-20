@pushOnce('styles', 'files-css')
  <style>
    .min-width-0 {
        min-width: 0;
    }

    [data-file-card] {
        scroll-margin-top: 1rem;
    }

    .file-browser-scroll {
        max-height: 24rem;
        overflow-y: auto;
    }

    .file-browser-scroll.has-vertical-overflow {
        overscroll-behavior: contain;
    }

    .file-browser-scroll.has-open-file-actions {
        max-height: none;
        overflow: visible;
    }

    .file-image-item:hover .file-image-card,
    .file-image-item:focus-within .file-image-card,
    .file-image-item.is-selected .file-image-card {
        border-color: #80bdff !important;
        box-shadow: 0 0 0 0.15rem rgba(0, 123, 255, 0.12);
    }

    .file-image-select {
        height: 88px;
        border-radius: 0.2rem 0.2rem 0 0;
    }

    .file-image-thumbnail {
        object-fit: cover;
    }

    .file-image-item {
        align-self: flex-start;
    }

    .file-image-caption {
        min-height: 34px;
    }

    .file-item-edit-region {
        flex-basis: 100%;
    }

    .file-list-item.is-selected {
        background: #f8f9fa;
    }

    .file-details-preview,
    .file-details-icon {
        flex: 0 0 104px;
        width: 104px;
        height: 78px;
    }

    .file-details-preview img {
        object-fit: contain;
    }

    .file-list-item.file-reference-highlight,
    .file-image-item.file-reference-highlight .file-image-card {
        background-color: #fff8db;
        border-color: #d6a800 !important;
        box-shadow: 0 0 0 0.2rem rgba(214, 168, 0, 0.2);
    }

    @media (max-width: 575.98px) {
        .file-details-preview,
        .file-details-icon {
            width: 100%;
            height: 104px;
        }
    }
  </style>
@endPushOnce
