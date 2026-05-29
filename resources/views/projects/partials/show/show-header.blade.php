@php
  $modules = collect($project->activeModuleSlugs());

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

      @if ($project->isOrganizational())
        <a href="{{ route('projects.subprojects', $project) }}"
          class="btn btn-sm {{ $routeName === 'projects.subprojects' ? 'btn-secondary' : 'btn-outline-secondary' }}">
          Subprojetos
        </a>
      @endif

      @foreach ($modules as $module)
        {{-- o botão do menu do módulo pode não existir --}}
        @includeIf("module-{$module}.partials.project-menu-item")
      @endforeach

    </div>
  </div>

  <div class="d-flex align-items-center gap-2">
    @include('projects.partials.show.show-tag-badges')
    @include('projects.partials.show.settings-btn')
  </div>
</div>
