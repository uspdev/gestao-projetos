@php
  $notesDisplayId = 'meeting-notes-display-' . $meeting->id;
  $notesEditId = 'meeting-notes-edit-' . $meeting->id;
  $isEditingNotes = old('form_context') === 'meeting-notes';
  $canEditNotes = $meeting->status !== \App\Enums\Meeting\MeetingStatus::COMPLETED;
@endphp

<div class="card mb-4 shadow-sm">
  <div class="card-header h5 py-2">Anotações prévias</div>
  <div class="card-body">
    <div class="d-flex align-items-center justify-content-between mb-2">
      <span class="text-muted">Preparação geral da reunião</span>
      @if ($canEditNotes)
        @can('update', [$meeting, $project])
          <button type="button" class="btn btn-outline-primary btn-sm py-0" data-toggle="collapse"
            data-target="#{{ $notesDisplayId }}, #{{ $notesEditId }}" aria-label="Editar Anotações prévias">
            <i class="fas fa-edit"></i>
          </button>
        @endcan
      @endif
    </div>

    <div class="collapse {{ $isEditingNotes ? '' : 'show' }}" id="{{ $notesDisplayId }}">
      @if (filled($meeting->notes))
        <div class="text-dark text-break">
          <x-markdown-content :text="$meeting->notes" :escape-html="true" :render-markdown="false" />
        </div>
      @else
        <span class="text-muted">-</span>
      @endif
    </div>

    @if ($canEditNotes)
      @can('update', [$meeting, $project])
        <div class="collapse {{ $isEditingNotes ? 'show' : '' }}" id="{{ $notesEditId }}">
          <form method="POST" action="{{ route('projects.meetings.updateNotes', [$project, $meeting]) }}" class="mt-2">
            @csrf
            @method('PATCH')
            <input type="hidden" name="form_context" value="meeting-notes">

            <label for="{{ $notesEditId }}-textarea" class="sr-only">Anotações prévias</label>
            <x-form.textarea name="meeting_notes" :id="$notesEditId . '-textarea'" :value="$meeting->notes" groupClass="mb-2"
              rows="3" maxlength="10000" />

            <div class="d-flex justify-content-end" style="gap: 0.5rem;">
              <x-form.cancel-button class="btn-sm" data-toggle="collapse"
                data-target="#{{ $notesDisplayId }}, #{{ $notesEditId }}" />
              <x-form.save-button class="btn btn-primary btn-sm" />
            </div>
          </form>
        </div>
      @endcan
    @endif
  </div>
</div>
