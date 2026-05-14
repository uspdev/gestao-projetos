<div class="card mb-4 shadow-sm">
  <div class="card-header h5 d-flex justify-content-start align-items-center">
    <span>
      <i class="fas fa-sitemap"></i> Subprojetos
    </span>
    <div class="d-flex align-items-center gap-2 ml-2">
      @can('create', \App\Models\Project::class)
        <button class="btn btn-sm btn-success" type="button" data-toggle="modal" data-target="#modalNovoProjeto"
          data-structure="subproject" data-parent-id="{{ $project->id }}" data-lock-parent="true">
          <i class="fas fa-plus"></i>
        </button>
      @endcan

      @include('projects.partials.buttons.link-subproject-btn')
    </div>
  </div>

  <div class="card-body">
    <div class="row">
      @forelse ($subprojects as $subproject)
        @php
          $subprojectTags = $subproject->tags->where('type', \App\Models\Tag::TYPE_PROJECT);
        @endphp
        <div class="col-md-6 col-lg-6">
          <x-card.preview class="mb-3" href="{{ route('projects.show', $subproject) }}"
            aria-label="Acessar subprojeto {{ $subproject->name }}" :title="$subproject->name" title-variant="project"
            :status-label="$subproject->status?->label()" :status-class="$subproject->status?->color()"
            :project-type="$subproject->projectType?->name" :tags="$subprojectTags" :tags-limit="1">
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
