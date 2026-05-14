<div class="card mb-4 shadow-sm">
  <div class="card-header h5">
    <i class="fas fa-sitemap"></i> Subprojetos
  </div>

  <div class="card-body">
    <div class="row">
      @forelse ($subprojects as $subproject)
        @php
          $subprojectTags = $subproject->tags->where('type', \App\Models\Tag::TYPE_PROJECT);
        @endphp
        <div class="col-md-6 col-lg-4">
          <x-card.preview class="mb-3" href="{{ route('projects.show', $subproject) }}"
            aria-label="Acessar subprojeto {{ $subproject->name }}" :title="$subproject->name" title-variant="project"
            :status-label="$subproject->status?->label()" :status-class="$subproject->status?->color()" :project-type="$subproject->projectType?->name" :tags="$subprojectTags" :tags-limit="2">
            <x-slot:footer>
              <div class="d-flex flex-wrap" style="gap: 0.35rem;">
                <span class="badge badge-light border text-muted">
                  <i class="fas fa-tasks mr-1"></i>{{ $subproject->tasks_count }} tasks
                </span>
                <span class="badge badge-light border text-muted">
                  <i class="fas fa-users mr-1"></i>{{ $subproject->users_count }} membros
                </span>
              </div>
            </x-slot:footer>
          </x-card.preview>
        </div>
      @empty
        <div class="col-12">
          <div class="alert alert-light border mb-0">
            Nenhum subprojeto cadastrado.
          </div>
        </div>
      @endforelse
    </div>
  </div>
</div>
