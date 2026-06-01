<section id="todos-projetos">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h4 class="mb-1">Todos os projetos</h4>
      <p class="text-muted mb-0"><span id="projects-count">{{ $projects->count() }}</span> projeto(s)
        encontrado(s).</p>
    </div>
  </div>

  <div class="row" id="projects-list">
    @foreach ($projects as $project)
      <div class="col-md-4 project-item"
        data-searchable="{{ strtolower($project->name . ' ' . ($project->description ?? '') . ' ' . ($project->tags->pluck('name')->implode(' ') ?? '')) }}">
        @include('projects.partials.components.preview')
      </div>
    @endforeach
  </div>
  <div class="row">
    <div class="col-12">
      <div id="no-results" class="alert alert-info d-none">Nenhum projeto encontrado para sua busca.</div>
    </div>
  </div>
</section>
