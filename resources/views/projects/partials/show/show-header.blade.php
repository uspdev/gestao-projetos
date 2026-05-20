@php
  $modules = ['tasks', 'meetings'];
  $routeName = Route::currentRouteName();
@endphp

<div class="card-header d-flex justify-content-between align-items-center gap-2 card-header-sticky">
  <div class="mb-0">
    <div class="h4 mb-0 d-flex align-items-center flex-wrap" style="gap: 0.35rem;">
      <a href="{{ route('projects.index') }}" class="text-decoration-none text-secondary">
        <i class="fas fa-home"></i>
      </a>
      <x-separator />

      @if ($project->isSubproject() && $project->parent)
        <a href="{{ route('projects.show', $project->parent) }}" class="text-decoration-none text-secondary">
          {{ $project->parent->name }}
        </a>
        <x-separator />
      @endif

      <span>{{ $project->name }}</span>

      @if ($project->isSubproject())
        <span class="badge badge-pill badge-info" style="font-size: 0.75rem; padding: 0.2rem 0.4rem;"
          title="Este projeto é um subprojeto">
          SUB
        </span>
      @endif

      <a href="{{ route('projects.show', $project) }}"
        class="btn btn-sm {{ $routeName === 'projects.show' ? 'btn-secondary' : 'btn-outline-secondary' }}">
        Visão geral
      </a>

      @foreach ($modules as $module)
        @if ($project->isModuleEnabled($module))
          @include("module-{$module}.partials.project-menu-item")
        @endif
      @endforeach

      <a href="{{ route('projects.settings', $project) }}" class="btn btn-sm btn-outline-secondary">
        Fase > <span class="badge {{ $project->phase->color() }}">{{ $project->phase->label() }}</span>
      </a>

    </div>
  </div>

  <div class="d-flex align-items-center gap-2">
    @include('projects.partials.show.show-tag-badges')

    <a href="{{ route('projects.settings', $project) }}"
      class="btn btn-sm
     {{ $routeName === 'projects.settings' ? 'btn-warning' : 'btn-outline-warning' }}">
      <i class="fas fa-cog"></i>
    </a>
  </div>
</div>
