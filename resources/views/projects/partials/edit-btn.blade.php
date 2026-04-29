@can('update', $project)
  <button type="button" class="btn btn-outline-primary btn-sm" data-toggle="modal" data-target="#modalEditarProjeto">
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

        <form action="{{ route('projects.update', $project->id) }}" method="POST">
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
              {{-- Descrição --}}
              <div class="col-12">
                <x-form.textarea name="description" label="Descrição Detalhada" value="{{ $project->description }}"
                  rows="4" />
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

  {{-- Reabre o modal caso haja erro de validação na edição --}}
  {{-- todo: Em qual card vai estre JS ???????? --}}
  @if ($errors->any() && old('_method') === 'PUT')
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const editBtn = document.querySelector('[data-target="#modalEditarProjeto"]');
        if (editBtn) editBtn.click();
      });
    </script>
  @endif

@endcan
