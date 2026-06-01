@can('storeAssignee', $task)
  <button class="btn btn-sm btn-outline-success py-0" title="Atribuir usuário" type="button" data-toggle="modal"
    data-target="#addTaskAssigneeModal">
    <i class="fas fa-plus"></i>
  </button>

  @push('modals')
    <div class="modal fade" id="addTaskAssigneeModal" tabindex="-1" role="dialog" aria-labelledby="addTaskAssigneeModalLabel"
      aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="addTaskAssigneeModalLabel">Adicionar responsável à tarefa</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
              <span aria-hidden="true">&times;</span>
            </button>
          </div>
          <form method="POST" action="{{ route('tasks.assignees.store', $task) }}">
            @csrf
            <div class="modal-body">
              <div id="task-assignee-empty-state" class="alert alert-light border text-muted mb-3 d-none"></div>

              <div class="form-group mb-0">
                <label for="task-assignee-user-id" class="font-weight-bold">Usuário</label>
                <select id="task-assignee-user-id" name="user_id"
                  class="form-control @error('user_id') is-invalid @enderror" disabled required>
                  <option value="">Carregando usuários...</option>
                </select>
                @error('user_id')
                  <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
              </div>
            </div>
            <div class="modal-footer">
              <x-form.cancel-button data-dismiss="modal" />
              <button type="submit" class="btn btn-primary" id="task-assignee-confirm-btn" disabled>
                Confirmar
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  @endpush

  @push('scripts')
    @if ($errors->has('user_id'))
      <script>
        document.addEventListener('DOMContentLoaded', function() {
          const openBtn = document.querySelector('[data-target="#addTaskAssigneeModal"]');
          if (openBtn) openBtn.click();
        });
      </script>
    @endif
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('addTaskAssigneeModal');
        const userSelect = document.getElementById('task-assignee-user-id');
        const confirmBtn = document.getElementById('task-assignee-confirm-btn');
        const emptyState = document.getElementById('task-assignee-empty-state');
        const loadUrl = '{{ route('tasks.assignees.selectable', $task) }}';
        const oldUserId = '{{ old('user_id', '') }}';

        if (!modal || !userSelect || !confirmBtn || !emptyState) {
          return;
        }

        let usersLoaded = false;

        const setSelectLoading = function() {
          userSelect.innerHTML = '<option value="">Carregando usuários...</option>';
          userSelect.setAttribute('disabled', 'disabled');
          confirmBtn.setAttribute('disabled', 'disabled');
          emptyState.classList.add('d-none');
          emptyState.textContent = '';
        };

        const fillUserSelect = function(users) {
          userSelect.innerHTML = '<option value="">Selecione...</option>';

          users.forEach(function(candidate) {
            const option = document.createElement('option');
            option.value = String(candidate.id);
            option.textContent = candidate.name + ' (' + candidate.email + ')';
            userSelect.appendChild(option);
          });

          if (oldUserId) {
            userSelect.value = String(oldUserId);
          }
        };

        const loadSelectableUsers = function() {
          if (usersLoaded) return;

          setSelectLoading();

          fetch(loadUrl, {
              headers: {
                'X-Requested-With': 'XMLHttpRequest'
              }
            })
            .then(function(response) {
              if (!response.ok) throw new Error('Falha ao carregar usuários.');
              return response.json();
            })
            .then(function(users) {
              if (!Array.isArray(users) || users.length === 0) {
                userSelect.innerHTML = '<option value="">Nenhum usuário disponível</option>';
                emptyState.textContent = 'Não há usuários disponíveis para atribuir a esta tarefa.';
                emptyState.classList.remove('d-none');
                return;
              }

              fillUserSelect(users);
              userSelect.removeAttribute('disabled');
              confirmBtn.removeAttribute('disabled');
              usersLoaded = true;
            })
            .catch(function() {
              userSelect.innerHTML = '<option value="">Erro ao carregar usuários</option>';
              emptyState.textContent = 'Não foi possível carregar a lista de usuários.';
              emptyState.classList.remove('d-none');
            });
        };

        const openButtons = document.querySelectorAll('[data-target="#addTaskAssigneeModal"]');
        openButtons.forEach(function(btn) {
          btn.addEventListener('click', loadSelectableUsers);
        });
      });
    </script>
  @endpush
@endcan
