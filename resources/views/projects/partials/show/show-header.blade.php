@php
  // Tem pagina que não tem resolvedModules, enão chama direto pelo projeto que esta sendo mostrado
  $resolvedModules ??= isset($project) ? \App\Models\Module::resolveForProject($project) : [];

  $modules = collect($resolvedModules)->filter(fn($module) => $module['enabled'] ?? false)->pluck('slug');

  if ($modules->contains('phases')) {
      $modules = $modules->reject('phases')->push('phases');
  }

  $modules = $modules->values()->all();

  $routeName = Route::currentRouteName();
@endphp

@section('styles')
  @parent
  <style>
    .border-bottom-2 {
      border-bottom-width: 2px !important;
    }
  </style>
@endsection

<div @class([
    'card-header d-flex justify-content-between align-items-center gap-2 card-header-sticky',
    'border-bottom border-warning border-bottom-2' => request()->routeIs(
        'projects.settings'),
])>
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

      @if (!$project->isSubproject())
        <a href="{{ route('projects.subprojects', $project) }}"
          class="btn btn-sm {{ $routeName === 'projects.subprojects' ? 'btn-secondary' : 'btn-outline-secondary' }}">
          Subprojetos
        </a>
      @endif

      @foreach ($modules as $module)
        @if ($project->isModuleEnabled($module))
          @include("module-{$module}.partials.project-menu-item")
        @endif
      @endforeach

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
