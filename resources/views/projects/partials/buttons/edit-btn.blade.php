@can('update', $project)
  <button type="button" class="btn btn-outline-primary btn-sm py-0" data-toggle="modal" data-target="#modalEditarProjeto">
    <i class="fas fa-edit"></i>
  </button>

  @push('scripts')
    <div class="modal fade" id="modalEditarProjeto" tabindex="-1" aria-labelledby="modalEditarProjetoLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modalEditarProjetoLabel">Editar Projeto: {{ $project->name }}</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>

          <form action="{{ route('projects.update', $project) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="modal-body">
              <div class="row">
                {{-- Nome --}}
                <div class="col">
                  <x-form.input name="name" label="Nome do Projeto" value="{{ $project->name }}" required minlength="3"
                    maxlength="50" />
                </div>

                {{-- Status (oculto — atualizado por outro método) --}}
                <input type="hidden" name="status" id="status" value="{{ old('status', $project->status?->value) }}">
              </div>

              <div class="row">
                <div class="col-12">
                  <x-form.input name="slug" label="URL do Projeto (Slug)" value="{{ $project->slug }}" maxlength="80"
                    pattern="[a-z0-9]+(?:-[a-z0-9]+)*" title="Use apenas letras minusculas, numeros e hifens."
                    autocomplete="off" autocapitalize="none" spellcheck="false" />
                  <small class="text-muted d-block">Aviso: Alterar a URL quebrará links antigos já compartilhados.</small>
                  <small class="text-muted d-block">Use apenas letras minúsculas, números e hifens. Acentos serão
                    removidos.</small>
                </div>
              </div>

              @php
                $projectTypeValue = old('project_type_id', $project->project_type_id);
                $visibilityValue = old(
                    'visibility',
                    $project->visibility?->value ?? \App\Enums\Project\ProjectVisibility::PRIVATE->value,
                );
                $permissionValue = old(
                    'permission_inheritance',
                    $project->permission_inheritance?->value ??
                        \App\Enums\Project\ProjectPermissionInheritance::FULL->value,
                );
                $phaseValue = old('phase', $project->phase?->value ?? \App\Enums\Project\ProjectPhase::PLANNING->value);
              @endphp

              <div class="row">
                <div class="col-md-6">
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
                </div>

                <div class="col-md-6">
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

              <div class="row">
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

                <div class="col-md-6">
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
              </div>

              <div class="row">
                {{-- Descrição --}}
                <div class="col-12">
                  <x-form.textarea name="description" label="Descrição Detalhada" value="{{ $project->description }}"
                    rows="4" maxlength="10000" />
                </div>
              </div>

              {{-- Tags adicionadas na edição --}}
              <div class="row">
                <div class="col-12">
                  <div class="form-group mb-3">
                    <label>Tags</label>
                    <select name="tags[]" multiple style="width: 100%;"
                      class="form-control select2-tags @error('tags') is-invalid @enderror">
                      @foreach (App\Models\Tag::forProjects() as $tag)
                        <option value="{{ $tag->id }}"
                          {{ in_array($tag->id, $project->tags->pluck('id')->toArray(), true) ? 'selected' : '' }}>
                          {{ $tag->name }}
                        </option>
                      @endforeach
                    </select>

                    @error('tags.*')
                      <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                  </div>
                </div>
              </div>
            </div>

            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i> Salvar Alterações
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  @endpush

  @push('scripts')
    @include('projects.partials.scripts.multi-select-script')

    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('modalEditarProjeto');
        if (!modal) return;

        const slugInput = modal.querySelector('input[name="slug"]');
        if (!slugInput) return;

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

        slugInput.addEventListener('input', normalizeSlugInput);
        slugInput.addEventListener('blur', function() {
          slugInput.reportValidity();
        });

        normalizeSlugInput();
      });
    </script>

    {{-- Reabre o modal caso haja erro de validação na edição.
         Movido para dentro do section para melhor organização do JS --}}
    @if ($errors->any() && old('_method') === 'PUT')
      <script>
        document.addEventListener('DOMContentLoaded', function() {
          const editBtn = document.querySelector('[data-target="#modalEditarProjeto"]');
          if (editBtn) editBtn.click();
        });
      </script>
    @endif
  @endpush
@endcan
