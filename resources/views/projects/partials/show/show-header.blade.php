@php
  $routeName = Route::currentRouteName();
  $tasksEnabled = $project->isModuleEnabled('tasks');
  $meetingsEnabled = $project->isModuleEnabled('meetings');
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
      <a href="{{ route('projects.show', $project) }}"
        class="btn btn-sm {{ $routeName === 'projects.show' ? 'btn-secondary' : 'btn-outline-secondary' }}">
        Visão geral
      </a>

      @if ($tasksEnabled)
        <a href="{{ route('projects.tasks.index', $project) }}"
          class="btn btn-sm {{ $routeName === 'projects.tasks.index' ? 'btn-secondary' : 'btn-outline-secondary' }}">
          Tarefas
        </a>
      @endif

      @if ($meetingsEnabled)
        <a href="{{ route('projects.meetings.index', $project) }}"
          class="btn btn-sm {{ $routeName === 'projects.meetings.index' ? 'btn-secondary' : 'btn-outline-secondary' }}">
          Reuniões
        </a>
      @endif
      <span class="badge badge-pill badge-primary" style="font-size: 0.75rem; padding: 0.2rem 0.4rem;">
        {{ $project->status?->label() }}</span>
      @if ($project->isSubproject())
        <span class="badge badge-pill badge-info" style="font-size: 0.7rem; padding: 0.2rem 0.4rem;">
          SUBPROJETO
        </span>
      @endif
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
