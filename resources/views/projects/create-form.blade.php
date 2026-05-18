@extends('layouts.app')

@section('title', 'Novo Projeto')

@section('content')
  @php
    $visibilityValue = old('visibility', \App\Enums\Project\ProjectVisibility::PRIVATE->value);
    $permissionValue = old('permission_inheritance', \App\Enums\Project\ProjectPermissionInheritance::FULL->value);
    $phaseValue = old('phase', \App\Enums\Project\ProjectPhase::PLANNING->value);
    $projectTypeValue = old('project_type_id', $projectType->id);
    $selectedTags = collect(old('tags', []))->map(fn($id) => (int) $id)->all();
    $activeModules = $projectType->modules->filter(fn($module) => (bool) ($module->pivot?->enabled ?? false))->values();
  @endphp

  <div class="container-fluid">
    <div class="d-flex justify-content-between align-items-start mb-4">
      <div>
        <h2 class="mb-1">Novo projeto</h2>
        <p class="text-muted mb-0">Tipo selecionado: <strong>{{ $projectType->name }}</strong></p>
      </div>
      <a class="btn btn-outline-secondary" href="{{ route('projects.create') }}">
        <i class="fas fa-arrow-left"></i> Voltar
      </a>
    </div>

    <div class="row">
      <div class="col-lg-8">
        <div class="card">
          <div class="card-body">
            <form action="{{ route('projects.store') }}" method="POST" data-project-form="create">
              @csrf
              <input type="hidden" name="project_type_id" value="{{ $projectTypeValue }}">
              <input type="hidden" name="permission_inheritance" value="{{ $permissionValue }}">

              <div class="row">
                <div class="col-md-8">
                  <x-form.input name="name" label="Nome" required minlength="3" maxlength="50" />
                </div>
                <div class="col-md-4">
                  <x-form.input name="slug" label="Slug" required maxlength="80" pattern="[a-z0-9]+(?:-[a-z0-9]+)*"
                    title="Use apenas letras minusculas, numeros e hifens." autocomplete="off" autocapitalize="none"
                    spellcheck="false" />
                  <small class="text-muted d-block">Use apenas letras minúsculas, números e hifens.</small>
                </div>
              </div>

              <div class="row">
                <div class="col-md-4">
                  <div class="form-group mb-3">
                    <label for="status">Estado <span class="text-danger">*</span></label>
                    <select name="status" id="status" class="form-control @error('status') is-invalid @enderror"
                      required>
                      <option value="">Selecione...</option>
                      @foreach (\App\Enums\Project\ProjectStatus::cases() as $status)
                        <option value="{{ $status->value }}" {{ old('status') === $status->value ? 'selected' : '' }}>
                          {{ $status->label() }}
                        </option>
                      @endforeach
                    </select>
                    @error('status')
                      <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group mb-3">
                    <label for="phase">Fase <span class="text-danger">*</span></label>
                    <select name="phase" id="phase" class="form-control @error('phase') is-invalid @enderror"
                      required>
                      @foreach (\App\Enums\Project\ProjectPhase::cases() as $phase)
                        <option value="{{ $phase->value }}" {{ $phaseValue === $phase->value ? 'selected' : '' }}>
                          {{ $phase->label() }}
                        </option>
                      @endforeach
                    </select>
                    @error('phase')
                      <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group mb-3">
                    <label for="visibility">Visibilidade <span class="text-danger">*</span></label>
                    <select name="visibility" id="visibility"
                      class="form-control @error('visibility') is-invalid @enderror" required>
                      @foreach (\App\Enums\Project\ProjectVisibility::cases() as $visibility)
                        <option value="{{ $visibility->value }}"
                          {{ $visibilityValue === $visibility->value ? 'selected' : '' }}>
                          {{ $visibility->label() }}
                        </option>
                      @endforeach
                    </select>
                    @error('visibility')
                      <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                  </div>
                </div>
              </div>

              <div class="form-group mb-3">
                <label>Tags</label>
                <select name="tags[]" multiple style="width: 100%;"
                  class="form-control select2-tags @error('tags') is-invalid @enderror @error('tags.*') is-invalid @enderror">
                  @foreach (App\Models\Tag::forProjects() as $tag)
                    <option value="{{ $tag->id }}" {{ in_array($tag->id, $selectedTags, true) ? 'selected' : '' }}>
                      {{ $tag->name }}
                    </option>
                  @endforeach
                </select>
                @error('tags.*')
                  <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
              </div>

              <x-form.textarea name="description" label="Descrição" rows="3" maxlength="10000" />

              <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">
                  <i class="fas fa-save"></i> Salvar Projeto
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-4">
        <div class="card mb-3">
          <div class="card-body">
            <h5 class="card-title">{{ $projectType->name }}</h5>
            @if ($projectType->description)
              <div class="text-muted"><x-markdown-content :text="$projectType->description" :escape-html="false" /></div>
            @else
              <p class="text-muted">Sem descrição cadastrada para este tipo de projeto.</p>
            @endif
            <div>
              <strong class="d-block mb-2">Módulos ativos</strong>
              @if ($activeModules->isNotEmpty())
                <ul class="mb-0">
                  @foreach ($activeModules as $module)
                    <li>{{ $module->name }}</li>
                  @endforeach
                </ul>
              @else
                <div class="text-muted">Nenhum módulo ativo.</div>
              @endif
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
@endsection

@push('scripts')
  @include('projects.partials.scripts.multi-select-script')
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.querySelector('[data-project-form="create"]');
      if (!form) return;

      const nameInput = form.querySelector('input[name="name"]');
      const slugInput = form.querySelector('input[name="slug"]');

      if (!nameInput || !slugInput) return;

      const slugify = (value) => {
        return value
          .toString()
          .normalize('NFD')
          .replace(/[\u0300-\u036f]/g, '')
          .toLowerCase()
          .replace(/[^a-z0-9]+/g, '-')
          .replace(/(^-|-$)+/g, '');
      };

      const normalizeSlugInput = () => {
        const normalized = slugify(slugInput.value);

        if (slugInput.value !== normalized) {
          slugInput.value = normalized;
        }

        if (slugInput.value.trim() === '') {
          slugInput.setCustomValidity('Informe um slug valido usando letras minusculas, numeros e hifens.');
          return;
        }

        slugInput.setCustomValidity('');
      };

      let isSlugDirty = false;

      if (slugInput.value.trim() !== '' && slugInput.value !== slugify(nameInput.value)) {
        isSlugDirty = true;
      }

      slugInput.addEventListener('input', function() {
        isSlugDirty = true;
        normalizeSlugInput();
      });

      slugInput.addEventListener('blur', function() {
        slugInput.reportValidity();
      });

      nameInput.addEventListener('input', function() {
        if (isSlugDirty) {
          return;
        }

        slugInput.value = slugify(nameInput.value);
        normalizeSlugInput();
      });

      normalizeSlugInput();
    });
  </script>
@endpush
