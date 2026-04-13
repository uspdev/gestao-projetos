@can('storeMember', $project)
<div class="modal fade" id="addProjectMemberModal" tabindex="-1" role="dialog" aria-labelledby="addProjectMemberModalLabel" aria-hidden="true">
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

                    <div class="form-group">
                        <label for="member-user-id" class="font-weight-bold">Usuário</label>
                        <select id="member-user-id" name="user_id" class="form-control @error('user_id') is-invalid @enderror" disabled>
                            <option value="">Carregando usuários...</option>
                        </select>
                        @error('user_id')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group mb-0">
                        <label for="member-role" class="font-weight-bold">Role no projeto</label>
                        <select id="member-role" name="role" class="form-control @error('role') is-invalid @enderror">
                            <option value="">Selecione...</option>
                            @foreach (\App\Enums\Project\ProjectUserRole::cases() as $role)
                                <option value="{{ $role->value }}" @selected(old('role') === $role->value)>
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
                    <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="project-member-confirm-btn" disabled>
                        Confirmar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Scripts do Modal 100% Vanilla JS --}}
@if ($errors->has('user_id') || $errors->has('role'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Em vez de usar jQuery .modal('show'), disparamos um clique no botão de abrir
            const openBtn = document.querySelector('[data-target="#addProjectMemberModal"]');
            if (openBtn) openBtn.click();
        });
    </script>
@endif

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('addProjectMemberModal');
        const userSelect = document.getElementById('member-user-id');
        const confirmBtn = document.getElementById('project-member-confirm-btn');
        const emptyState = document.getElementById('project-member-empty-state');
        const loadUrl = '{{ route("projects.members.selectable", $project) }}';
        const oldUserId = '{{ old("user_id", "") }}';

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
                    emptyState.textContent = 'Não há usuários disponíveis para adicionar a este projeto.';
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

        const openButtons = document.querySelectorAll('[data-target="#addProjectMemberModal"]');
        openButtons.forEach(function(btn) {
            btn.addEventListener('click', loadSelectableUsers);
        });
    });
</script>
@endcan