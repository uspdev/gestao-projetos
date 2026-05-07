@php
  // $class(['btn', 'btn-sm', 'btn-outline-secondary'=> $routeName === 'projects.tasks.index'])>

  $routeName = Route::currentRouteName();
  echo $routeName;
@endphp

<div class="card-header d-flex justify-content-between align-items-center gap-2 card-header-sticky">
  <div class="h4 mb-0">
    <a href="{{ route('projects.index') }}" class="text-decoration-none text-secondary">
      <i class="fas fa-home"></i>
    </a>
    <x-separator />
    <span class="btn btn-sm btn-primary"> {{ $project->status?->label() }}</span>
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
    @include('projects.partials.show.show-tag-badges')


    <a href="{{ route('projects.settings', $project) }}"
      class="btn btn-sm
     {{ $routeName === 'projects.settings' ? 'btn-warning' : 'btn-outline-warning' }}">
      <i class="fas fa-cog"></i> Configurações
    </a>

  </div>

</div>
