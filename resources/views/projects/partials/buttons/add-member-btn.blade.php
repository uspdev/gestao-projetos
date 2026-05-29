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

              <div class="form-group">
                <label for="member-codpes" class="font-weight-bold">Usuário</label>
                <select id="member-codpes" name="codpes" class="form-control @error('codpes') is-invalid @enderror"
                  style="width: 100%;" required>
                  <option value="">Digite o nome ou codpes..</option>
                </select>
                @error('codpes')
                  <div class="invalid-feedback d-block">{{ $message }}</div>
                @enderror
              </div>

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
        const modal = document.getElementById('addProjectMemberModal');
        const userSelect = $('#member-codpes');
        const confirmBtn = document.getElementById('project-member-confirm-btn');
        const emptyState = document.getElementById('project-member-empty-state');
        const loadUrl = '{{ route('projects.members.selectable', $project) }}';
        const oldCodpes = '{{ old('codpes', '') }}';

        if (!modal || !userSelect.length || !emptyState || !confirmBtn) {
          return;
        }

        let selectInitialized = false;

        const initSelect2 = function() {
          if (selectInitialized) {
            return;
          }

          userSelect.select2({
            ajax: {
              url: loadUrl,
              dataType: 'json',
              delay: 1000,
              data: function(params) {
                return {
                  term: params.term
                };
              },
              processResults: function(data) {
                return data;
              }
            },
            dropdownParent: $('#addProjectMemberModal'),
            minimumInputLength: 4,
            theme: 'bootstrap4',
            width: '100%',
            language: 'pt-BR',
            placeholder: 'Digite o nome ou codpes..'
          });

          userSelect.on('change', function() {
            if ($(this).val()) {
              confirmBtn.removeAttribute('disabled');
            } else {
              confirmBtn.setAttribute('disabled', 'disabled');
            }
          });

          selectInitialized = true;

          if (oldCodpes) {
            fetch(loadUrl + '?term=' + encodeURIComponent(oldCodpes), {
                headers: {
                  'X-Requested-With': 'XMLHttpRequest'
                }
              })
              .then(function(response) {
                if (!response.ok) throw new Error('Falha ao carregar usuário selecionado.');
                return response.json();
              })
              .then(function(data) {
                if (!data || !Array.isArray(data.results) || data.results.length === 0) {
                  return;
                }

                const candidate = data.results[0];
                const option = new Option(candidate.text, candidate.id, true, true);
                userSelect.append(option).trigger('change');
              });
          }
        };

        $('#addProjectMemberModal').on('shown.bs.modal', function() {
          initSelect2();
          $('#member-codpes').select2('open');
        });

        $(document).on('select2:open', function() {
          const searchField = document.querySelector('.select2-search__field');
          if (searchField) {
            searchField.focus();
          }
        });
      });
    </script>
  @endpush
@endcan
