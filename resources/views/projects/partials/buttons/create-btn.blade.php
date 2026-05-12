@can('create', \App\Models\Project::class)
  <button class="btn btn-success" type="button" data-toggle="modal" data-target="#modalNovoProjeto">
    <i class="fas fa-plus"></i> Novo Projeto
  </button>

  <div class="modal fade" id="modalNovoProjeto" tabindex="-1" aria-labelledby="modalNovoProjetoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="modalNovoProjetoLabel">Novo Projeto</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <form action="{{ route('projects.store') }}" method="POST">
          @csrf
          <div class="modal-body">
            <x-form.input name="name" label="Nome" required minlength="3" maxlength="50" />
            <x-form.input name="slug" label="Slug (identificador amigável na URL)" required maxlength="80"
              pattern="[a-z0-9]+(?:-[a-z0-9]+)*" title="Use apenas letras minusculas, numeros e hifens."
              autocomplete="off" autocapitalize="none" spellcheck="false" />
            <small class="text-muted d-block">Use apenas letras minúsculas, números e hifens. Acentos serão
              removidos.</small>

            <div class="form-group mb-3">
              <label for="status">Estado <span class="text-danger">*</span></label>
              <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
                <option value="">Selecione o estado...</option>
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

            @php
              $projectTypeValue = old('project_type_id');
              $visibilityValue = old('visibility', \App\Enums\Project\ProjectVisibility::PRIVATE->value);
              $permissionValue = old(
                  'permission_inheritance',
                  \App\Enums\Project\ProjectPermissionInheritance::FULL->value,
              );
              $phaseValue = old('phase', \App\Enums\Project\ProjectPhase::PLANNING->value);
            @endphp

            <div class="form-group mb-3">
              <label for="project_type_id">Tipo de projeto</label>
              <select name="project_type_id" id="project_type_id"
                class="form-control @error('project_type_id') is-invalid @enderror">
                <option value="">Sem tipo</option>
                @foreach (App\Models\ProjectType::query()->orderBy('name')->get() as $projectType)
                  <option value="{{ $projectType->id }}"
                    {{ (string) $projectTypeValue === (string) $projectType->id ? 'selected' : '' }}>
                    {{ $projectType->name }}
                  </option>
                @endforeach
              </select>
              @error('project_type_id')
                <div class="invalid-feedback d-block">{{ $message }}</div>
              @enderror
            </div>

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

            <div class="form-group mb-3">
              <label for="permission_inheritance">Herança de permissões <span class="text-danger">*</span></label>
              <select name="permission_inheritance" id="permission_inheritance"
                class="form-control @error('permission_inheritance') is-invalid @enderror" required>
                @foreach (\App\Enums\Project\ProjectPermissionInheritance::cases() as $permissionInheritance)
                  <option value="{{ $permissionInheritance->value }}"
                    {{ $permissionValue === $permissionInheritance->value ? 'selected' : '' }}>
                    {{ $permissionInheritance->label() }}
                  </option>
                @endforeach
              </select>
              @error('permission_inheritance')
                <div class="invalid-feedback d-block">{{ $message }}</div>
              @enderror
            </div>

            <div class="form-group mb-3">
              <label for="phase">Fase <span class="text-danger">*</span></label>
              <select name="phase" id="phase" class="form-control @error('phase') is-invalid @enderror" required>
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

            <x-form.textarea name="description" label="Descrição" rows="3" maxlength="10000" />

            <div class="form-group mb-3">
              <label>Tags</label>

              @php
                $selectedTags = collect(old('tags', []))->map(fn($id) => (int) $id)->all();
              @endphp

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
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-save"></i> Salvar Projeto
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  @section('javascripts_bottom')
    @parent
    @include('projects.partials.scripts.multi-select-script')
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('modalNovoProjeto');
        if (!modal) return;

        const nameInput = modal.querySelector('input[name="name"]');
        const slugInput = modal.querySelector('input[name="slug"]');

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
  @endsection

  @if ($errors->any())
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        $('#modalNovoProjeto').modal('show');
      });
    </script>
  @endif
@endcan
