@can('storeMember', $project)
  <button class="btn btn-sm btn-outline-success py-0" title="Adicionar membro" type="button" data-toggle="modal"
    data-target="#addProjectMemberModal">
    <i class="fas fa-plus"></i>
  </button>

  @push('modals')
    <div class="modal fade" id="addProjectMemberModal" tabindex="-1" role="dialog"
      aria-labelledby="addProjectMemberModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="addProjectMemberModalLabel">Adicionar membro ao projeto</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <form method="POST" action="{{ route('projects.members.store', $project) }}">
            @csrf
            <div class="modal-body">
              <div id="project-member-empty-state" class="alert alert-light border text-muted mb-3 d-none"></div>

              <x-form.usp-person-select name="codpes" id="member-codpes" label="Usuário"
                label-class="font-weight-bold" :url="route('projects.members.selectable', $project)"
                :required="true" :auto-open="true" placeholder="Digite o nome ou codpes.." />

              <div class="form-group mb-0">
                <label for="member-role" class="font-weight-bold">Função no projeto</label>
                <select id="member-role" name="role" class="form-control @error('role') is-invalid @enderror" required>
                  <option value="">Selecione...</option>
                  @foreach (\App\Enums\Project\ProjectUserRole::cases() as $role)
                    <option value="{{ $role->value }}" @selected(old('role', \App\Enums\Project\ProjectUserRole::CONTRIBUTOR->value) === $role->value)>
                      {{ $role->label() }}
                    </option>
                  @endforeach
                </select>
                @error('role')
                  <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
              </div>
            </div>
            <div class="modal-footer">
              <x-form.cancel-button data-dismiss="modal" />
              <button type="submit" class="btn btn-primary" id="project-member-confirm-btn" disabled>
                Confirmar
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  @endpush

  @push('scripts')
    @if ($errors->has('codpes') || $errors->has('role'))
      <script>
        document.addEventListener('DOMContentLoaded', function() {
          const openBtn = document.querySelector('[data-target="#addProjectMemberModal"]');
          if (openBtn) openBtn.click();
        });
      </script>
    @endif

    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const userSelect = $('#member-codpes');
        const confirmBtn = document.getElementById('project-member-confirm-btn');
        if (!userSelect.length || !confirmBtn) return;

        const updateConfirmButton = function() {
          confirmBtn.disabled = !userSelect.val();
        };
        userSelect.on('change', updateConfirmButton);
        updateConfirmButton();
      });
    </script>
  @endpush
@endcan
