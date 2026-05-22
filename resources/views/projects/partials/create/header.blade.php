<div class="d-flex justify-content-between align-items-start mb-4">
  <div>
    <h2 class="mb-1">Novo projeto</h2>
    <p class="text-muted mb-0">Tipo selecionado: <strong>{{ $projectType->name }}</strong></p>
  </div>
  <a class="btn btn-outline-secondary" href="{{ route('projects.create') }}">
    <i class="fas fa-arrow-left"></i> Voltar
  </a>
</div>
