{{-- Card: Título e Descrição --}}
@php
  $type = $type ?? null;
  $bg_color = '';
  $class = '';
  if ($type == 'main') {
      $bg_color = 'lightcyan';
      $class = 'h5';
  }
  $descriptionDisplayId = 'project-description-display-' . $project->id;
  $descriptionEditId = 'project-description-edit-' . $project->id;
  $isEditingDescription =
      old('form_context') === 'project-description' && (string) old('project_id') === (string) $project->id;
@endphp

<div class="card mb-4">
  <div class="card-header {{ $class }} py-2" style="background-color: {{ $bg_color }};">
    <div class="d-flex align-items-center justify-content-between" style="gap: 0.75rem;">
      <span>Descrição</span>
      @can('update', $project)
        <button type="button" class="btn btn-outline-primary btn-sm py-0" data-toggle="collapse"
          data-target="#{{ $descriptionDisplayId }}, #{{ $descriptionEditId }}" aria-label="Editar descrição">
          <i class="fas fa-edit"></i>
        </button>
      @endcan
    </div>
  </div>

  <div class="card-body">
    {{-- Descrição --}}
    <div class="text-justify collapse {{ $isEditingDescription ? '' : 'show' }}" id="{{ $descriptionDisplayId }}">
      @if ($project->description)
        <x-markdown.markdown-content :text="$project->description" />
      @else
        <div class="text-center text-muted p-5 bg-light rounded">
          <i class="fas fa-align-left fa-3x mb-3 text-secondary"></i>
          <h5>Sem descrição</h5>
          <p class="mb-0">Nenhuma descrição foi fornecida para este projeto.</p>
        </div>
      @endif
    </div>

    @can('update', $project)
      <div class="collapse {{ $isEditingDescription ? 'show' : '' }}" id="{{ $descriptionEditId }}">
        <form method="POST" action="{{ route('projects.updateDescription', $project) }}" class="mt-3">
          @csrf
          @method('PATCH')
          <input type="hidden" name="form_context" value="project-description">
          <input type="hidden" name="project_id" value="{{ $project->id }}">

          <label for="{{ $descriptionEditId }}-textarea" class="sr-only">Editar descrição</label>
          <x-form.textarea name="description" :id="$descriptionEditId . '-textarea'" :value="$project->description" groupClass="mb-2" markdown-profile="full" rows="4"
            maxlength="10000" data-file-reference-url="{{ route('files.selectable', ['context_type' => 'project', 'context_id' => $project->id]) }}" />

          <div class="d-flex justify-content-end" style="gap: 0.5rem;">
            <x-form.cancel-button class="btn-sm" data-toggle="collapse"
              data-target="#{{ $descriptionDisplayId }}, #{{ $descriptionEditId }}" />
            <x-form.save-button class="btn btn-primary btn-sm" />
          </div>
        </form>
      </div>
    @endcan
  </div>

</div>
