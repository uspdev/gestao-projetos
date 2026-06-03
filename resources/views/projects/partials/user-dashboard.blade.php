<section id="projetos-pinnados" class="mb-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div class="h4 mb-1">
      <i class="fas fa-thumbtack fa-xs text-secondary"></i>
      Projetos em destaque
      <a href="{{ route('projects.index') }}" class="btn btn-sm btn-outline-primary">
        Todos os projetos
      </a>
    </div>
  </div>

  <div class="row">
    @forelse($user->pinnedProjects() as $project)
      <div class="col-md-4">
        @include('projects.partials.components.preview')
      </div>
    @empty
      <div class="col-12">
        <div class="alert alert-light border mb-0">
          Você ainda não fixou nenhum projeto.
        </div>
      </div>
    @endforelse
  </div>
</section>

