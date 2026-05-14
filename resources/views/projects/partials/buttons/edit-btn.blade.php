@can('update', $project)
  <button type="button" class="btn btn-outline-primary btn-sm py-0" data-toggle="modal" data-target="#modalEditarProjeto">
    <i class="fas fa-edit"></i>
  </button>

  @push('scripts')
    <div class="modal fade" id="modalEditarProjeto" tabindex="-1" aria-labelledby="modalEditarProjetoLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="modalEditarProjetoLabel">Editar Descrição: {{ $project->name }}</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>

          <form action="{{ route('projects.updateDescription', $project) }}" method="POST">
            @csrf
            @method('PATCH')

            <div class="modal-body">
              <div class="row">
                {{-- Descrição --}}
                <div class="col-12">
                  <x-form.textarea name="description" label="Descrição Detalhada" value="{{ $project->description }}"
                    rows="6" maxlength="10000" />
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
    {{-- Reabre o modal caso haja erro de validação na edição. --}}
    @if ($errors->any() && old('_method') === 'PATCH' && request()->route()->getName() === 'projects.updateDescription')
      <script>
        document.addEventListener('DOMContentLoaded', function() {
          const editBtn = document.querySelector('[data-target="#modalEditarProjeto"]');
          if (editBtn) editBtn.click();
        });
      </script>
    @endif
  @endpush
@endcan
