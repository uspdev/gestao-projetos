@can('create', \App\Models\Project::class)
  @php
    $showCreateButton = $showCreateButton ?? true;
  @endphp

  @if ($showCreateButton)
    <button class="btn btn-success" type="button" data-toggle="modal" data-target="#modalNovoProjeto">
      <i class="fas fa-plus"></i> Novo Projeto
    </button>
  @endif

  <div class="modal fade" id="modalNovoProjeto" tabindex="-1" aria-labelledby="modalNovoProjetoLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
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
            @php
              $projectTypeValue = old('project_type_id');
              $visibilityValue = old('visibility', \App\Enums\Project\ProjectVisibility::PRIVATE->value);
              $permissionValue = old(
                  'permission_inheritance',
                  \App\Enums\Project\ProjectPermissionInheritance::FULL->value,
              );
              $phaseValue = old('phase', \App\Enums\Project\ProjectPhase::PLANNING->value);
              $structureValue = old(
                  'structure_type',
                  isset($contextParentProject) && $contextParentProject ? 'subproject' : 'independent',
              );
              $parentIdValue = old('parent_id', $contextParentProject?->id ?? null);
              $parentProjects = $parentProjects ?? collect();
            @endphp

            <!-- Row 1: Nome e Slug -->
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

            <!-- Row 1.5: Estrutura do Projeto -->
            <div class="card border mb-3">
              <div class="card-header bg-light font-weight-bold">
                Estrutura do projeto
              </div>
              <div class="card-body">
                <div class="form-group mb-3">
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="structure_type" id="structureIndependent"
                      value="independent" {{ $structureValue === 'independent' ? 'checked' : '' }}>
                    <label class="form-check-label" for="structureIndependent">Projeto independente</label>
                  </div>
                  <div class="form-check form-check-inline">
                    <input class="form-check-input" type="radio" name="structure_type" id="structureSubproject"
                      value="subproject" {{ $structureValue === 'subproject' ? 'checked' : '' }}>
                    <label class="form-check-label" for="structureSubproject">Subprojeto</label>
                  </div>
                  @error('structure_type')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                  @enderror
                </div>

                <div id="projectParentGroup" class="form-group mb-0">
                  <label for="parent_id">Projeto pai <span class="text-danger">*</span></label>
                  <select name="parent_id" id="parent_id" class="form-control @error('parent_id') is-invalid @enderror">
                    <option value="">Selecione...</option>
                    @foreach ($parentProjects as $parentProject)
                      <option value="{{ $parentProject->id }}"
                        {{ (string) $parentIdValue === (string) $parentProject->id ? 'selected' : '' }}>
                        {{ $parentProject->name }}
                      </option>
                    @endforeach
                  </select>
                  <input type="hidden" name="parent_id" id="parent_id_hidden" value="{{ $parentIdValue }}" disabled>
                  @error('parent_id')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                  @enderror
                </div>
              </div>
            </div>

            <!-- Row 2: Estado, Fase, Visibilidade -->
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

            <!-- Row 3: Tags e Herança de Permissões -->
            <div class="row">
              <div class="col-md-6">
                <div class="form-group mb-3">
                  <label>Tags</label>

                  @php
                    $selectedTags = collect(old('tags', []))->map(fn($id) => (int) $id)->all();
                  @endphp

                  <select name="tags[]" multiple style="width: 100%;"
                    class="form-control select2-tags @error('tags') is-invalid @enderror @error('tags.*') is-invalid @enderror">

                    @foreach (App\Models\Tag::forProjects() as $tag)
                      <option value="{{ $tag->id }}"
                        {{ in_array($tag->id, $selectedTags, true) ? 'selected' : '' }}>
                        {{ $tag->name }}
                      </option>
                    @endforeach
                  </select>

                  @error('tags.*')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                  @enderror
                </div>
              </div>
              <div class="col-md-6">
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
              </div>
            </div>

            <!-- Row 4: Tipo de Projeto -->
            <div class="form-group mb-3">
              <label for="project_type_id">Tipo de projeto <span class="text-danger">*</span></label>
              <select name="project_type_id" id="project_type_id"
                class="form-control @error('project_type_id') is-invalid @enderror" required>
                <option value="">Selecione...</option>
                @foreach (App\Models\ProjectType::query()->orderBy('name')->get() as $projectType)
                  <option value="{{ $projectType->id }}"
                    {{ (string) $projectTypeValue === (string) $projectType->id ? 'selected' : '' }}
                    @if ($projectType->description) data-description="{{ $projectType->description }}" @endif>
                    {{ $projectType->name }}
                  </option>
                @endforeach
              </select>
              @if (count(App\Models\ProjectType::query()->orderBy('name')->get()) > 0)
                <small id="projectTypeDescription" class="text-muted d-block mt-1"></small>
              @endif
              @error('project_type_id')
                <div class="invalid-feedback d-block">{{ $message }}</div>
              @enderror
            </div>

            <!-- Row 5: Descrição -->
            <x-form.textarea name="description" label="Descrição" rows="3" maxlength="10000" />
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

  @push('scripts')
    @include('projects.partials.scripts.multi-select-script')
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('modalNovoProjeto');
        if (!modal) return;

        const nameInput = modal.querySelector('input[name="name"]');
        const slugInput = modal.querySelector('input[name="slug"]');
        const projectTypeSelect = modal.querySelector('select[name="project_type_id"]');
        const projectTypeDescription = document.getElementById('projectTypeDescription');
        const parentGroup = modal.querySelector('#projectParentGroup');
        const parentSelect = modal.querySelector('select[name="parent_id"]');
        const parentHidden = modal.querySelector('#parent_id_hidden');
        const structureInputs = modal.querySelectorAll('input[name="structure_type"]');

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

        // Atualizar descrição do tipo de projeto
        if (projectTypeSelect && projectTypeDescription) {
          const updateProjectTypeDescription = () => {
            const selectedOption = projectTypeSelect.options[projectTypeSelect.selectedIndex];
            const description = selectedOption.getAttribute('data-description');

            if (description) {
              projectTypeDescription.textContent = description;
            } else {
              projectTypeDescription.textContent = '';
            }
          };

          projectTypeSelect.addEventListener('change', updateProjectTypeDescription);
          updateProjectTypeDescription();
        }

        const setStructureState = (structureValue, parentId = null, lockParent = false) => {
          if (!parentGroup || !parentSelect || !parentHidden) return;

          const isSubproject = structureValue === 'subproject';
          parentGroup.style.display = isSubproject ? 'block' : 'none';
          parentSelect.required = isSubproject;

          if (parentId !== null) {
            parentSelect.value = String(parentId);
            parentHidden.value = String(parentId);
          }

          if (lockParent && isSubproject) {
            parentSelect.setAttribute('disabled', 'disabled');
            parentHidden.removeAttribute('disabled');
          } else {
            parentSelect.removeAttribute('disabled');
            parentHidden.setAttribute('disabled', 'disabled');
          }
        };

        const updateStructureFromInputs = () => {
          const selected = modal.querySelector('input[name="structure_type"]:checked');
          setStructureState(selected ? selected.value : 'independent');
        };

        if (structureInputs.length) {
          structureInputs.forEach((input) => {
            input.addEventListener('change', updateStructureFromInputs);
          });
          updateStructureFromInputs();
        }

        if (window.jQuery && typeof jQuery === 'function') {
          jQuery(modal).on('show.bs.modal', function(event) {
            const trigger = event.relatedTarget;
            if (!trigger) return;

            const structure = trigger.getAttribute('data-structure');
            const parentId = trigger.getAttribute('data-parent-id');
            const lockParent = trigger.getAttribute('data-lock-parent') === 'true';

            if (structure) {
              const inputToCheck = modal.querySelector(
                `input[name="structure_type"][value="${structure}"]`,
              );
              if (inputToCheck) {
                inputToCheck.checked = true;
              }
              setStructureState(structure, parentId, lockParent);
            }
          });
        }
      });
    </script>
  @endpush

  @if ($errors->any())
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        $('#modalNovoProjeto').modal('show');
      });
    </script>
  @endif
@endcan
