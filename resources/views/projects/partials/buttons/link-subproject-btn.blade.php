@php
  $canLinkSubprojects = !$project->isSubproject() && $project->isOrganizational();
@endphp
{{-- Permissão de storeMember atende os requesitos de que o usuário possa vincular subprojetos a este projeto --}}
@can('storeMember', $project)
  @if ($canLinkSubprojects)
    <button class="btn btn-sm btn-outline-primary py-0" type="button" data-toggle="modal" data-target="#linkSubprojectModal">
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
                <section class="mb-4">
                  <h6 class="font-weight-bold mb-1">Projeto Existente</h6>
                  <p class="text-muted mb-3">Selecione um projeto existente e vincule-o como subprojeto.</p>

                  <div class="form-group mb-0">
                    <label for="subproject-id" class="sr-only">Projeto existente</label>
                    <select id="subproject-id" name="subproject_id"
                      class="form-control @error('subproject_id') is-invalid @enderror" style="width: 100%;" required>
                      <option value="">Selecione...</option>
                      @foreach ($project->linkableSubprojects() as $candidate)
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
                </section>

                @can('create', \App\Models\Project::class)
                  <section>
                    <h6 class="font-weight-bold mb-1">Projeto Novo</h6>
                    <p class="text-muted mb-3">Crie um projeto novo e vincule-o como subprojeto.</p>

                    <a href="{{ route('projects.create', ['parent_id' => $project->id]) }}"
                      class="btn btn-outline-success">
                      <i class="fas fa-plus mr-1" aria-hidden="true"></i>
                      Criar novo projeto
                    </a>
                  </section>
                @endcan
              </div>
              <div class="modal-footer">
                <x-form.cancel-button data-dismiss="modal" />
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
