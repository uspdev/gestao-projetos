<div class="card">
  <div class="card-body">
    <form action="{{ route('projects.store') }}" method="POST" data-project-form="create">
      @csrf
      <input type="hidden" name="project_type_id" value="{{ $projectTypeValue }}">
      <input type="hidden" name="permission_inheritance" value="{{ $permissionValue }}">
      @if ($parentProject ?? null)
        <input type="hidden" name="parent_id" value="{{ $parentProject->id }}">
      @endif

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
            <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
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
        @if ($phasesEnabled)
          <div class="col-md-4">
            <div class="form-group mb-3">
              <label for="phase_id">Fase <span class="text-danger">*</span></label>
              <select name="phase_id" id="phase_id" class="form-control @error('phase_id') is-invalid @enderror"
                required>
                <option value="">Selecione...</option>
                @foreach ($availablePhases as $phase)
                  <option value="{{ $phase->id }}"
                    {{ (string) $phaseValue === (string) $phase->id ? 'selected' : '' }}>
                    {{ $phase->name }}
                  </option>
                @endforeach
              </select>
              @error('phase_id')
                <div class="invalid-feedback d-block">{{ $message }}</div>
              @enderror
            </div>
          </div>
        @endif
        <div class="col-md-4">
          <div class="form-group mb-3">
            <label for="visibility">Visibilidade <span class="text-danger">*</span></label>
            <select name="visibility" id="visibility" class="form-control @error('visibility') is-invalid @enderror"
              required>
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
        <x-form.save-button class="btn btn-primary" label="Salvar Projeto" />
      </div>
    </form>
  </div>
</div>
