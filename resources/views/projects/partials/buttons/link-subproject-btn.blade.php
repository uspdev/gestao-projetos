@php
  $linkableSubprojects = $linkableSubprojects ?? collect();
@endphp
{{-- Permissão de storeMember atende os requesitos de que o usuário possa vincular subprojetos a este projeto --}}
@can('storeMember', $project)
  @if (!$project->isSubproject())
    <button class="btn btn-sm btn-outline-primary" type="button" data-toggle="modal" data-target="#linkSubprojectModal">
      <i class="fas fa-link"></i>
    </button>

    @push('modals')
      <div class="modal fade" id="linkSubprojectModal" tabindex="-1" role="dialog" aria-labelledby="linkSubprojectModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="linkSubprojectModalLabel">Vincular subprojeto</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <form method="POST" action="{{ route('projects.subprojects.link', $project) }}">
              @csrf
              <div class="modal-body">
                <div class="alert alert-light border text-muted mb-3">
                  Selecione um projeto independente para vincular como subprojeto.
                </div>

                <div class="form-group mb-0">
                  <label for="subproject-id" class="font-weight-bold">Projeto independente</label>
                  <select id="subproject-id" name="subproject_id"
                    class="form-control @error('subproject_id') is-invalid @enderror" style="width: 100%;" required>
                    <option value="">Selecione...</option>
                    @foreach ($linkableSubprojects as $candidate)
                      @php
                        $adminName = $candidate->users->first()?->name ?? 'N/A';
                        $statusLabel = $candidate->status?->label() ?? 'Sem status';
                        $typeLabel = $candidate->projectType?->name ?? 'Sem tipo';
                      @endphp
                      <option value="{{ $candidate->id }}"
                        {{ (string) old('subproject_id') === (string) $candidate->id ? 'selected' : '' }}>
                        {{ $candidate->name }} - {{ $statusLabel }} - Admin: {{ $adminName }} - Tipo:
                        {{ $typeLabel }}
                      </option>
                    @endforeach
                  </select>
                  @error('subproject_id')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                  @enderror
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-primary" id="link-subproject-confirm">
                  Vincular
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    @endpush

    @push('scripts')
      @if ($errors->has('subproject_id'))
        <script>
          document.addEventListener('DOMContentLoaded', function() {
            const openBtn = document.querySelector('[data-target="#linkSubprojectModal"]');
            if (openBtn) openBtn.click();
          });
        </script>
      @endif
    @endpush
  @endif
@endcan
