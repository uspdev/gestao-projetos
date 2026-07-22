@php
  $sourceProject = $project;
  $modalId = 'duplicate-project-modal-' . $sourceProject->id;
  $isDuplicationForm = old('duplication_form') === 'project';
  $copySuffix = '(Cópia)';
  $suggestedName = \Illuminate\Support\Str::limit($sourceProject->name, 50 - mb_strlen($copySuffix), '') . $copySuffix;
  $nameValue = $isDuplicationForm ? old('name') : $suggestedName;
  $copyMembers = $isDuplicationForm ? (string) old('copy_members', '0') === '1' : true;
  $copyTasks = $isDuplicationForm ? (string) old('copy_tasks', '0') === '1' : false;
  $copyMeetings = $isDuplicationForm ? (string) old('copy_meetings', '0') === '1' : false;
  $projectMeetings = $sourceProject->meetings()->orderBy('scheduled_at')->orderBy('title')->get();
@endphp

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}-label"
  aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="{{ $modalId }}-label">Duplicar projeto</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <form method="POST"
        action="{{ route('projects.duplicates.store', [$sourceProject, 'project', $sourceProject->id]) }}">
        @csrf
        <input type="hidden" name="duplication_form" value="project">

        <div class="modal-body">
          <p class="text-muted">
            O novo projeto será independente, o slug será gerado
            novamente, e status e fase serão reiniciados.
          </p>

          <div class="form-group mb-3">
            <label for="{{ $modalId }}-name">Nome da cópia <span class="text-danger">*</span></label>
            <input type="text" name="name" id="{{ $modalId }}-name" value="{{ $nameValue }}"
              minlength="3" maxlength="50" required @class([
                  'form-control',
                  'is-invalid' => $isDuplicationForm && $errors->has('name'),
              ])>
            @if ($isDuplicationForm && $errors->has('name'))
              <div class="invalid-feedback d-block">{{ $errors->first('name') }}</div>
            @endif
          </div>

          <div class="card border mb-3">
            <div class="card-header py-2 font-weight-bold">Dados opcionais</div>
            <div class="card-body py-3">
              <input type="hidden" name="copy_members" value="0">
              <div class="custom-control custom-checkbox mb-3">
                <input type="checkbox" class="custom-control-input" id="{{ $modalId }}-copy-members"
                  name="copy_members" value="1" @checked($copyMembers)>
                <label class="custom-control-label" for="{{ $modalId }}-copy-members">Copiar membros</label>
                <small class="form-text text-muted">
                  Usuários e papéis serão copiados sem a fixação. Você será administrador da cópia.
                </small>
              </div>

              <input type="hidden" name="copy_tasks" value="0">
              <div class="custom-control custom-checkbox mb-3">
                <input type="checkbox" class="custom-control-input" id="{{ $modalId }}-copy-tasks"
                  name="copy_tasks" value="1" @checked($copyTasks)>
                <label class="custom-control-label" for="{{ $modalId }}-copy-tasks">Copiar tarefas</label>
                <small class="form-text text-muted">
                  Sem copiar os membros, as tarefas manterão o estágio atual e ficarão sem responsáveis.
                </small>
              </div>

              <input type="hidden" name="copy_meetings" value="0">
              <div class="custom-control custom-checkbox mb-0">
                <input type="checkbox" class="custom-control-input" id="{{ $modalId }}-copy-meetings"
                  name="copy_meetings" value="1" @checked($copyMeetings) @disabled($projectMeetings->isEmpty())>
                <label class="custom-control-label" for="{{ $modalId }}-copy-meetings">Copiar reuniões</label>
                @if ($projectMeetings->isEmpty())
                  <small class="form-text text-muted">Este projeto não possui reuniões para copiar.</small>
                @else
                  <small class="form-text text-muted">As reuniões serão copiadas com as datas e horários atuais.</small>
                @endif
              </div>
            </div>
          </div>

          @if ($projectMeetings->isNotEmpty() || ($copyTasks || $copyMeetings))
            <div class="alert alert-warning" role="alert">
              Ao copiar reuniões e tarefas, confira as datas e horários das cópias antes de usá-las.
            </div>
          @endif
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-copy mr-1" aria-hidden="true"></i> Duplicar projeto
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

@if ($isDuplicationForm && $errors->any())
  @push('scripts')
    <script>
      $(function() {
        $('#{{ $modalId }}').modal('show');
      });
    </script>
  @endpush
@endif
