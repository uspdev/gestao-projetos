@can('update', $project)
  <button type="button" class="btn btn-outline-primary btn-sm py-0" data-toggle="modal" data-target="#modalEditarProjeto">
    <i class="fas fa-edit"></i>
  </button>

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
                <x-form.input name="name" label="Nome do Projeto" value="{{ $project->name }}" required />
              </div>

              {{-- Status (oculto — atualizado por outro método) --}}
              <input type="hidden" name="status" id="status" value="{{ old('status', $project->status?->value) }}">
            </div>

            <div class="row">
              <div class="col-12">
                <x-form.input name="slug" label="URL do Projeto (Slug)" value="{{ $project->slug }}" />
                <small class="text-muted d-block">Aviso: Alterar a URL quebrará links antigos já compartilhados.</small>
              </div>
            </div>

            <div class="row">
              {{-- Descrição --}}
              <div class="col-12">
                <x-form.textarea name="description" label="Descrição Detalhada" value="{{ $project->description }}"
                  rows="4" />
              </div>
            </div>

            {{-- Tags adicionadas na edição --}}
            <div class="row">
              <div class="col-12">
                <div class="form-group mb-3">
                  <label>Tags</label>

                  <select name="tags[]" multiple style="width: 100%;"
                    class="form-control select2-tags @error('tags') is-invalid @enderror">
                    @foreach ($availableTags as $tag)
                      <option value="{{ $tag->id }}"
                        {{ in_array($tag->id, $projectSelectedTags, true) ? 'selected' : '' }}>
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

  @section('javascripts_bottom')
    @parent
    @include('partials.multi-select-script')

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
  @endsection
@endcan
