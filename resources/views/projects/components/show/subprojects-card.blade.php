@props(['project', 'subprojects', 'type' => 'normal'])

@if ($project->isOrganizational())
  @php
    $isPreview = $type === 'preview';
    $subprojects = $project->subprojects();
    $totalSubprojects = count($subprojects);
    $displayedSubprojects = $isPreview ? $subprojects->take(2) : $subprojects;
  @endphp

  <x-projects::show.card-template :type="$type">
    <x-slot:header>
      <div class="d-flex align-items-center">
        {{-- 1. Título --}}
        <div class="mr-3">
          <i class="fas fa-sitemap"></i> Subprojetos
        </div>
        
        {{-- 2. Barra de Busca (Imediatamente após o título) --}}
        @if(!$isPreview && $totalSubprojects > 0)
          <div class="mr-2">
            @include('projects.partials.buttons.search-subproject-form')
          </div>
        @endif

        {{-- 3. Botão de Vínculo (Imediatamente após a busca) --}}
        <div>
          @include('projects.partials.buttons.link-subproject-btn')
        </div>
      </div>
    </x-slot:header>

    <div class="row">
      @forelse ($displayedSubprojects as $subproject)
        @php
          $subprojectTags = $subproject->tags->where('type', \App\Models\Tag::TYPE_PROJECT);
          $user = auth()->user();
          $canUnlinkSubproject =
              !$isPreview && $user && ($user->isAdminOfProject($project) || $user->isAdminOfProject($subproject));
          $userRole = $user ? $subproject->userRole($user) : null;
        @endphp
        
        <div class="col-md-6 col-lg-6 mb-3" data-subproject-searchable="{{ $subproject->name }}">
          <x-card.preview href="{{ route('projects.show', $subproject) }}"
            aria-label="Acessar subprojeto {{ $subproject->name }}" :title="$subproject->name" title-variant="project"
            :status-label="$subproject->status?->label()" :status-class="$subproject->status?->color()" :project-type="$subproject->projectType?->name" :tags="$subprojectTags" :tags-limit="1" :role-label="$userRole?->label() ?? 'Sem vínculo'"
            :role-class="$userRole ? 'badge-' . $userRole->color() : 'badge-light border text-muted'" action-class="preview-card__action">
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

      {{-- Alerta oculto que aparece quando a busca não encontra nada --}}
      <div id="subprojects-no-results" class="col-12 d-none">
        <div class="alert alert-light border text-center text-muted mb-0">
          Nenhum subprojeto encontrado na busca.
        </div>
      </div>
    </div>

    @if ($isPreview && $totalSubprojects > 2)
      <div class="mt-2 text-right">
        <a href="{{ route('projects.show', $project) }}?view=subprojects" class="text-primary small">
          Ver todos os subprojetos ({{ $totalSubprojects }})
        </a>
      </div>
    @endif

  </x-projects::show.card-template>
@endif