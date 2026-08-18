<div @class([
    'card-header d-flex justify-content-between align-items-center gap-2 card-header-sticky',
    'border-bottom border-warning' => request()->routeIs('projects.settings', 'projects.activity'),
])>
  <div class="mb-0">
    <div class="h4 mb-0 d-flex align-items-center flex-wrap" style="gap: 0.35rem;">
      <i class="fas fa-folder-open text-secondary"></i>

      {{-- projeto pai --}}
      @if ($project->isSubproject() && $project->parent)
        @include('projects.partials.show.project-name-menu-item', ['context' => 'parent'])
        @include('projects.partials.show.subprojects-menu-item', ['context' => 'parent'])
        @foreach ($project->parent->activeModulesForMenu() as $module)
          {{-- o botão do menu do módulo pode não existir --}}
          @includeIf("module-{$module}.partials.project-menu-item", ['context' => 'parent'])
        @endforeach
        <x-separator />
      @endif

      {{-- projeto atual --}}
      @include('projects.partials.show.project-name-menu-item')

      <div class="mb-2">
        @include('projects.partials.components.toggle-pin')
      </div>

      @if ($project->isOrganizational())
        @include('projects.partials.show.subprojects-menu-item')
      @endif

      @foreach ($project->activeModulesForMenu() as $module)
        {{-- o botão do menu do módulo pode não existir --}}
        @includeIf("module-{$module}.partials.project-menu-item")
      @endforeach

    </div>
  </div>

  <div class="d-flex align-items-center gap-2">
    @include('projects.partials.show.show-tag-badges')
    @include('projects.partials.show.activity-btn')
    @include('projects.partials.show.settings-btn')
  </div>
</div>
