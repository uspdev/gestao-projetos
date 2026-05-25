<small class="text-muted d-block mb-2">Projetos vinculados</small>
@php
  $linkedProjects = $meeting?->projects ?? collect();
@endphp
@if ($linkedProjects->isNotEmpty())
  <div class="d-flex flex-wrap" style="gap: 0.5rem;">
    @foreach ($linkedProjects as $linkedProject)
      <a href="{{ route('projects.show', $linkedProject) }}" class="badge badge-light border text-decoration-none">
        {{ $linkedProject->name }}
      </a>
    @endforeach
  </div>
@else
  <div class="text-muted">Nenhum projeto vinculado.</div>
@endif
