@props(['project'])

@php
  $user = auth()->user();
  $userRole = $user ? $project->userRole($user) : null;
  $allTags = $project->tagsWithType('projects');
  $canPin = (bool) $userRole;
  $isPinned = $canPin && $project->isPinnedBy($user);
@endphp

@once
  <style>
    .project-card {
      cursor: pointer;
      border: 1px solid #dee2e6;
      border-radius: .75rem;
      overflow: hidden;
      transition: all .2s ease;
      box-shadow: 0 .125rem .25rem rgba(0, 0, 0, .075);
      font-size: 0.9rem;
    }

    .project-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 .75rem 1.5rem rgba(0, 0, 0, .15);
    }

    .project-card__title {
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
      line-height: 1.3;
      font-weight: 800;
      color: #1f2937;
      margin-bottom: 0;
      /* padding-right: .5rem; */
    }

    .project-card__project {
      font-size: .9rem;
      color: #6c757d;
    }

    .project-card__meta {
      font-size: .85rem;
    }

    .project-card__tags {
      display: flex;
      align-items: center;
      flex-wrap: wrap;
      gap: .25rem;
    }

    .project-card__tags .badge {
      font-size: .75rem;
      padding: .35rem .5rem;
    }

    .project-card__footer {
      font-size: .82rem;
    }

    .project-card__action {
      opacity: 0;
      transition: opacity .2s ease;
    }

    .project-card:hover .project-card__action {
      opacity: 1;
    }
  </style>
@endonce

<div {{ $attributes->class(['card project-card position-relative h-100']) }}
  style="border-color: {{ $project->projectType->slug == 'organizacional' ? 'DodgerBlue' : '' }};">
  <div class="card-body d-flex flex-column">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-start mb-1">

      <div class="h5 project-card__title text-truncate">
        {{ $project->name }}
      </div>

      <div class="d-flex align-items-center">
        <span class="badge badge-{{ $project->status->color() }}">
          {{ $project->status->label() }}
        </span>

        <div class="ml-1" style="z-index: 2;">
          @include('projects.partials.components.toggle-pin')
        </div>
      </div>
    </div>

    {{-- body --}}
    <div class="text-muted mb-1">
      @if ($project->isSubproject())
        <i class="fas fa-folder-open mr-1"></i>
        {{ $project->parent?->name }}
      @else
        <span>&nbsp;</span>
      @endif
    </div>

    @if ($project->projectType)
      <div class="d-flex justify-content-start w-100 mb-2">
        <small class="text-muted d-flex align-items-center">
          <i class="fas fa-sitemap mr-1" title="Tipo de projeto"></i>
          <span>{{ $project->projectType->name }}</span>
        </small>
      </div>
    @endif

    {{-- Role --}}
    <div class="mb-2" title="Meu papel no projeto" style="z-index: 2;">
      <span class="badge badge-outline-{{ $userRole?->color() ?? 'secondary' }}">
        <i class="fas fa-user-circle mr-1"></i>
        {{ $userRole?->label() ?? 'Sem vínculo' }}
      </span>
    </div>

    {{-- Tags --}}
    @if ($allTags->isNotEmpty())
      <div class="d-flex flex-wrap">

        @foreach ($allTags->take(2) as $tag)
          <span class="badge {{ $tag->color ?? 'badge-light border text-muted' }} mr-1 mb-1">
            <i class="fas fa-tag mr-1"></i>
            {{ $tag->name }}
          </span>
        @endforeach

        @if ($allTags->count() > 2)
          <span class="badge badge-light border text-muted mr-1 mb-1">
            +{{ $allTags->count() - 2 }}
          </span>
        @endif

      </div>
    @endif

    {{-- <div class="mt-auto"></div> --}}

    {{-- Footer --}}

    <a href="{{ route('projects.show', $project) }}" class="stretched-link"
      aria-label="Acessar projeto {{ $project->name }}">
    </a>
  </div>
</div>
