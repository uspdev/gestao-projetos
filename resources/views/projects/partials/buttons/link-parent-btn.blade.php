@php
  $canLinkParent = !$project->isSubproject() && $project->parent === null;
@endphp
{{-- Permissão de storeMember atende os requisitos de que o usuário possa vincular projetos pais --}}
@can('storeMember', $project)
  @if ($canLinkParent)
    <button class="btn btn-sm btn-outline-primary py-0" type="button" data-toggle="modal" data-target="#linkParentModal">
      <i class="fas fa-link"></i>
    </button>

    @push('modals')
      <div class="modal fade" id="linkParentModal" tabindex="-1" role="dialog" aria-labelledby="linkParentModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="linkParentModalLabel">Vincular a projeto organizacional</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <form method="POST" action="{{ route('projects.parents.link', $project) }}">
              @csrf
              <div class="modal-body">
                <div class="alert">
                  Selecione um projeto organizacional para vincular.
                </div>

                <div class="form-group mb-0">
                  <label for="parent-id" class="font-weight-bold">Projeto organizacional</label>
                  <select id="parent-id" name="parent_id" class="form-control @error('parent_id') is-invalid @enderror"
                    style="width: 100%;" required>
                    <option value="">Selecione...</option>
                    @foreach ($project->linkableParents() as $candidate)
                      @php
                        $adminName = $candidate->users->first()?->name ?? 'N/A';
                        $statusLabel = $candidate->status?->label() ?? 'Sem status';
                        $typeLabel = $candidate->projectType?->name ?? 'Sem tipo';
                      @endphp
                      <option value="{{ $candidate->id }}"
                        {{ (string) old('parent_id') === (string) $candidate->id ? 'selected' : '' }}>
                        {{ $candidate->name }} - {{ $statusLabel }} - Admin: {{ $adminName }} - Tipo:
                        {{ $typeLabel }}
                      </option>
                    @endforeach
                  </select>
                  @error('parent_id')
                    <div class="invalid-feedback d-block">{{ $message }}</div>
                  @enderror
                </div>
              </div>
              <div class="modal-footer">
                <x-form.cancel-button data-dismiss="modal" />
                <button type="submit" class="btn btn-primary" id="link-parent-confirm">
                  Vincular
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    @endpush

    @push('scripts')
      @if ($errors->has('parent_id'))
        <script>
          document.addEventListener('DOMContentLoaded', function() {
            const openBtn = document.querySelector('[data-target="#linkParentModal"]');
            if (openBtn) openBtn.click();
          });
        </script>
      @endif
    @endpush
  @endif
@endcan
