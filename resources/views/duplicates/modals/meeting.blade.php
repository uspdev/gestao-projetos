@php
  $modalId = 'duplicate-meeting-modal-' . $meeting->id;
  $isDuplicationForm = old('duplication_form') === 'meeting';
  $copySuffix = '(Cópia)';
  $suggestedTitle = \Illuminate\Support\Str::limit($meeting->title, 120 - mb_strlen($copySuffix), '') . $copySuffix;
  $titleValue = $isDuplicationForm ? old('title') : $suggestedTitle;
  $scheduledAtValue = $isDuplicationForm ? old('scheduled_at') : $meeting->scheduled_at?->format('Y-m-d\TH:i');
  $scheduledAtIsPast = $meeting->scheduled_at?->isPast() ?? false;
@endphp

<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="{{ $modalId }}-label"
  aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="{{ $modalId }}-label">Duplicar reunião</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Fechar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <form method="POST" action="{{ route('projects.duplicates.store', [$project, 'meeting', $meeting->id]) }}">
        @csrf
        <input type="hidden" name="duplication_form" value="meeting">

        <div class="modal-body">
          <p class="text-muted">
            A cópia será criada com o status <strong>Agendada</strong>.
          </p>

          @if ($scheduledAtIsPast)
            <div class="alert alert-warning" role="alert">
              A data desta reunião já passou. Remarque a reunião para uma nova data.
            </div>
          @endif

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

          <div class="form-group mb-3">
            <label for="{{ $modalId }}-scheduled-at">Nova data e hora <span class="text-danger">*</span></label>
            <input type="datetime-local" name="scheduled_at" id="{{ $modalId }}-scheduled-at"
              value="{{ $scheduledAtValue }}" required @class([
                  'form-control',
                  'is-invalid' => $isDuplicationForm && $errors->has('scheduled_at'),
              ])>
            @if ($isDuplicationForm && $errors->has('scheduled_at'))
              <div class="invalid-feedback d-block">{{ $errors->first('scheduled_at') }}</div>
            @endif
          </div>

          <div class="form-group mb-3">
            <label class="font-weight-bold">Projetos vinculados</label>

            <div class="d-flex flex-wrap">
              @forelse ($meeting->projects as $project)
                <span class="badge badge-secondary badge-pill mr-1 mb-1 px-2 py-1">
                  {{ $project->name }}
                </span>
              @empty
                <span class="text-muted">Nenhum projeto vinculado</span>
              @endforelse
            </div>
          </div>

          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
            <button type="submit" class="btn btn-primary">
              <i class="fas fa-copy mr-1" aria-hidden="true"></i> Duplicar reunião
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
