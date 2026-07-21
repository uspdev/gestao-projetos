@php
  $modalId = 'duplicate-task-modal-' . $task->id;
  $isDuplicationForm = old('duplication_form') === 'task';
  $copySuffix = '(Cópia)';
  $suggestedTitle = \Illuminate\Support\Str::limit($task->title, 120 - mb_strlen($copySuffix), '') . $copySuffix;
  $titleValue = $isDuplicationForm ? old('title') : $suggestedTitle;
  $startDateValue = $isDuplicationForm ? old('start_date') : $task->start_date?->format('Y-m-d');
  $dueDateValue = $isDuplicationForm ? old('due_date') : $task->due_date?->format('Y-m-d');
@endphp

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}-label"
  aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="{{ $modalId }}-label">Duplicar tarefa</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <form method="POST" action="{{ route('projects.duplicates.store', [$task->project, 'task', $task->id]) }}">
        @csrf
        <input type="hidden" name="duplication_form" value="task">

        <div class="modal-body">
          <p class="text-muted">
            O status será reiniciado e a data de conclusão ficará vazia.
          </p>

          <div class="form-group mb-3">
            <label for="{{ $modalId }}-title">Nome da cópia <span class="text-danger">*</span></label>
            <input type="text" name="title" id="{{ $modalId }}-title" value="{{ $titleValue }}"
              minlength="3" maxlength="120" required @class([
                  'form-control',
                  'is-invalid' => $isDuplicationForm && $errors->has('title'),
              ])>
            @if ($isDuplicationForm && $errors->has('title'))
              <div class="invalid-feedback d-block">{{ $errors->first('title') }}</div>
            @endif
          </div>

          <div class="row">
            <div class="col-md-6">
              <div class="form-group mb-3">
                <label for="{{ $modalId }}-start-date">Data de início</label>
                <input type="date" name="start_date" id="{{ $modalId }}-start-date"
                  value="{{ $startDateValue }}" @class([
                      'form-control',
                      'is-invalid' => $isDuplicationForm && $errors->has('start_date'),
                  ])>
                @if ($isDuplicationForm && $errors->has('start_date'))
                  <div class="invalid-feedback d-block">{{ $errors->first('start_date') }}</div>
                @endif
              </div>
            </div>
            <div class="col-md-6">
              <div class="form-group mb-3">
                <label for="{{ $modalId }}-due-date">Data de entrega (prazo)</label>
                <input type="date" name="due_date" id="{{ $modalId }}-due-date" value="{{ $dueDateValue }}"
                  @class([
                      'form-control',
                      'is-invalid' => $isDuplicationForm && $errors->has('due_date'),
                  ])>
                @if ($isDuplicationForm && $errors->has('due_date'))
                  <div class="invalid-feedback d-block">{{ $errors->first('due_date') }}</div>
                @endif
              </div>
            </div>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-copy mr-1" aria-hidden="true"></i> Duplicar tarefa
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
