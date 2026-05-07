@php
  $routeName = Route::currentRouteName();
@endphp

<div class="card-header d-flex justify-content-between align-items-center gap-2 card-header-sticky">
  <div class="h4 mb-0">
    <a href="{{ route('projects.index') }}" class="text-decoration-none text-secondary">
      <i class="fas fa-home"></i>
    </a>
    <x-separator />
    <span class="badge badge-pill badge-primary" style="font-size: 0.75rem; padding: 0.2rem 0.4rem;"> {{ $project->status?->label() }}</span>
    {{ $project->name }}

    <a href="{{ route('projects.show', $project) }}"
      class="btn btn-sm {{ $routeName === 'projects.show' ? 'btn-secondary' : 'btn-outline-secondary' }}">
      Visão geral
    </a>

    <a href="{{ route('projects.tasks.index', $project) }}?view=list"
      class="btn btn-sm {{ $routeName === 'projects.tasks.index' ? 'btn-secondary' : 'btn-outline-secondary' }}">
      Tarefas
    </a>
  </div>

  <div class="d-flex align-items-center gap-2">
    @include('projects.partials.show-tag-badges')

    <a href="{{ route('projects.settings', $project) }}"
      class="btn btn-sm
     {{ $routeName === 'projects.settings' ? 'btn-warning' : 'btn-outline-warning' }}">
      <i class="fas fa-cog"></i> Configurações
    </a>
  </div>
</div>
