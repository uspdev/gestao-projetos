@php
  $routeName = Route::currentRouteName();
  $tasksEnabled = $project->isModuleEnabled('tasks');
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
      <span class="badge badge-pill badge-primary" style="font-size: 0.75rem; padding: 0.2rem 0.4rem;">
        {{ $project->status?->label() }}</span>
      @if ($project->isSubproject())
        <span class="badge badge-pill badge-info" style="font-size: 0.7rem; padding: 0.2rem 0.4rem;">
          SUBPROJETO
        </span>
      @endif
    </div>
    @if ($project->isSubproject() && $project->parent)
      <div class="text-muted small mt-1">
        Relacionado a:
        <a href="{{ route('projects.show', $project->parent) }}" class="text-decoration-none">
          {{ $project->parent->name }}
        </a>
      </div>
    @endif
    <div class="mt-2">
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
    </div>
  </div>

  <div class="d-flex align-items-center gap-2">
    @include('projects.partials.show.show-tag-badges')

    @can('create', \App\Models\Project::class)
      @if (!$project->isSubproject())
        <button class="btn btn-sm btn-success" type="button" data-toggle="modal" data-target="#modalNovoProjeto"
          data-structure="subproject" data-parent-id="{{ $project->id }}" data-lock-parent="true">
          <i class="fas fa-plus"></i> Novo Subprojeto
        </button>
      @endif
    @endcan

    @include('projects.partials.buttons.link-subproject-btn')

    <a href="{{ route('projects.settings', $project) }}"
      class="btn btn-sm
     {{ $routeName === 'projects.settings' ? 'btn-warning' : 'btn-outline-warning' }}">
      <i class="fas fa-cog"></i>
    </a>
  </div>
</div>
