@php
  $type = $type ?? null;
  $bg_color = '';
  $class = '';
  if ($type == 'main') {
      $bg_color = 'lightcyan';
      $class = 'h5';
  }
@endphp
<div class="card mb-4">
  <div class="card-header {{ $class }} py-2" style="background-color: {{ $bg_color }};">
    <i class="fas fa-sitemap"></i> Subprojetos
    @include('projects.partials.buttons.link-subproject-btn')
  </div>

  <div class="card-body">
    <div class="row">
      @forelse ($subprojects as $subproject)
        @php
          $subprojectTags = $subproject->tags->where('type', \App\Models\Tag::TYPE_PROJECT);
          $user = auth()->user();
          $canUnlinkSubproject = $user && ($user->isAdminOfProject($project) || $user->isAdminOfProject($subproject));
        @endphp
        <div class="col-md-6 col-lg-6 mb-3">
          <x-card.preview href="{{ route('projects.show', $subproject) }}"
            aria-label="Acessar subprojeto {{ $subproject->name }}" :title="$subproject->name" title-variant="project"
            :status-label="$subproject->status?->label()" :status-class="$subproject->status?->color()" :project-type="$subproject->projectType?->name" :tags="$subprojectTags" :tags-limit="1"
            action-class="preview-card__action">
            @if ($canUnlinkSubproject)
              <x-slot:action>
                @include('projects.partials.buttons.unlink-subproject-btn')
              </x-slot:action>
            @endif
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
