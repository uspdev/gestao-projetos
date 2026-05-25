@php
  $projectType = $project->projectType;
@endphp

<div class="card mb-3">
  <div class="card-header d-flex align-items-center py-2">
    <div class="d-flex align-items-center flex-wrap">
      <h6 class="m-0 text-muted mr-2">
        <i class="fas fa-layer-group mr-1"></i> Tipo do projeto
      </h6>
    </div>
  </div>
  <div class="card-body">
    @if ($projectType)
      <h5 class="card-title mb-3">{{ $projectType->name }}</h5>

      @if ($projectType->description)
        <div class="text-muted mb-3">
          <x-markdown-content :text="$projectType->description" :escape-html="false" />
        </div>
      @else
        <p class="text-muted mb-3">Sem descrição cadastrada para este tipo de projeto.</p>
      @endif
    @else
      <p class="text-muted mb-0">Este projeto ainda não possui tipo definido.</p>
    @endif
  </div>
</div>
