@php
  $parentProject = $parentProject ?? null;
  $creationTitle = $parentProject ? 'Novo subprojeto' : 'Novo projeto';
  $backParams = $parentProject ? ['parent_id' => $parentProject->id] : [];
@endphp

<div class="d-flex justify-content-between align-items-start mb-4">
  <div>
    <h2 class="mb-1">{{ $creationTitle }}</h2>
    <p class="text-muted mb-0">
      Tipo selecionado: <strong>{{ $projectType->name }}</strong>
      @if ($parentProject)
        <span class="d-block">
          Projeto pai:
          <a href="{{ deep_link('projects.show', [$parentProject, 'view' => 'subprojects']) }}">
            {{ $parentProject->name }}
          </a>
        </span>
      @endif
    </p>
  </div>
  <a class="btn btn-outline-secondary" href="{{ route('projects.create', $backParams) }}">
    <i class="fas fa-arrow-left"></i> Voltar
  </a>
</div>
