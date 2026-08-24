@pushOnce('styles')
  <style>
    .dashboard-pinned-projects-list {
      max-height: 22rem;
      overflow-y: auto;
      overflow-x: hidden;
      overscroll-behavior-y: auto;
      padding-right: .25rem;
    }
  </style>
@endPushOnce

<section id="projetos-pinnados" class="mb-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div class="h4 mb-1">
      <i class="fas fa-thumbtack fa-xs text-secondary"></i>
      Projetos fixados
      <a href="{{ route('projects.index') }}" class="btn btn-sm btn-outline-primary">
        Todos os projetos
      </a>
    </div>
  </div>

  <div class="dashboard-pinned-projects-list"
    style="max-height: 22rem; overflow-y: auto; overflow-x: hidden; overscroll-behavior-y: auto;"
    tabindex="0" aria-label="Projetos fixados">
    <div class="row">
      @forelse($user->pinnedProjects() as $project)
        <div class="col-12 project-item mb-2 px-2">
          <x-project-card :project="$project" view="compact"/>
        </div>
      @empty
        <div class="col-12">
          <div class="alert alert-light border mb-0">
            Sem projeto fixado.<br>
            Fixe um projeto para tê-lo sempre à mão.
          </div>
        </div>
      @endforelse
    </div>
  </div>
</section>
