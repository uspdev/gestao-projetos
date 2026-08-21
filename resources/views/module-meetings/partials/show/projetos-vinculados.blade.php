@php
  $linkedProjects = $meeting?->projects ?? collect();
@endphp
<div class="card border-0 meeting-context-card">
  <div class="card-header py-2">
    <i class="fas fa-folder-open"></i> Projetos vinculados
  </div>
  <div class="card-body p-0">
    <ul class="list-group list-group-flush">
      @forelse ($linkedProjects as $linkedProject)
        <li class="list-group-item d-flex align-items-center" style="gap: 0.5rem;">
          <span class="badge badge-light border text-muted">
            <i class="fas fa-folder"></i>
          </span>
          <a href="{{ deep_link('projects.show', $linkedProject) }}"
            class="text-decoration-none text-dark font-weight-bold">
            {{ $linkedProject->name }}
          </a>
        </li>
      @empty
        <li class="list-group-item text-muted font-italic small text-center py-3">
          Nenhum projeto vinculado.
        </li>
      @endforelse
    </ul>
  </div>
</div>
