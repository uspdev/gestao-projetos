@php
  $showToggle ??= false;
  $modules = $project->resolvedModules() ?? $project->activeModulesSummary();
  $canUpdateModules = $showToggle && isset($project) && auth()->user()?->can('updateModule', $project);
@endphp

<div class="card mb-4">
  <div class="card-header d-flex align-items-center py-2">
    <div class="d-flex align-items-center flex-wrap">
      <h6 class="m-0 text-muted mr-2">
        <i class="fas fa-puzzle-piece mr-1"></i> Módulos
      </h6>
    </div>
  </div>
  <ul class="list-group list-group-flush">
    @forelse ($modules as $module)
      <li
        class="list-group-item d-flex flex-column flex-sm-row align-items-start align-items-sm-center justify-content-between"
        style="gap: 0.5rem;">
        <span>{{ $module['name'] }}</span>
        <div class="d-flex align-items-center flex-wrap" style="gap: 0.5rem;">
          <span class="badge {{ $module['enabled'] ? 'badge-success' : 'badge-secondary' }}">
            {{ $module['enabled'] ? 'Ativo' : 'Inativo' }}
          </span>
          @if ($canUpdateModules)
            @include('projects.partials.show.module-toggle-form')
            @if ($module['required'] ?? false)
              <span class="text-muted small">Obrigatorio</span>
            @elseif (!($module['editable'] ?? true))
              <span class="text-muted small">Bloqueado</span>
            @endif
          @endif
        </div>
      </li>
    @empty
      <li class="list-group-item text-muted">Nenhum módulo configurado.</li>
    @endforelse
  </ul>
</div>
