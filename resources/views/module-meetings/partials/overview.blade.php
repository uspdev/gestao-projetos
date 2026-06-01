@php
  $notesDisplayId = 'meeting-notes-display-' . ($meeting?->id ?? 'novo');
  $notesEditId = 'meeting-notes-edit-' . ($meeting?->id ?? 'novo');
  $isEditingNotes =
      old('form_context') === 'meeting-notes' && (string) old('meeting_id') === (string) ($meeting?->id ?? '');
  $canEditNotes = $meeting && $project && $meeting->status !== \App\Enums\Meeting\MeetingStatus::COMPLETED;
@endphp

<div class="card mb-3">
  <div class="card-body p-0">
    <ul class="list-group list-group-flush">
      <li class="list-group-item">
        <small class="text-muted d-block">Data e hora</small>
        <strong>
          <x-local-date :date="$meeting->scheduled_at" :show-time="true" empty="-" />
        </strong>
      </li>

      <li class="list-group-item">
        <small class="text-muted d-block">Local</small>
        <strong>{{ $meeting->location ?? '-' }}</strong>
      </li>

      <li class="list-group-item p-0">
        @include('module-meetings.partials.show/projetos-vinculados')
      </li>

      <li class="list-group-item">
        <div class="d-flex align-items-center justify-content-between" style="gap: 0.5rem;">
          <small class="text-muted">Notas</small>
          @if ($canEditNotes)
            @can('update', [$meeting, $project])
              <button type="button" class="btn btn-outline-primary btn-sm py-0" data-toggle="collapse"
                data-target="#{{ $notesDisplayId }}, #{{ $notesEditId }}" aria-label="Editar notas">
                <i class="fas fa-edit"></i>
              </button>
            @endcan
          @endif
        </div>

        <div class="collapse {{ $isEditingNotes ? '' : 'show' }}" id="{{ $notesDisplayId }}">
          @if (filled($meeting->notes))
            <div class="text-dark">{{ $meeting->notes }}</div>
          @else
            <span class="text-muted">-</span>
          @endif
        </div>

        @if ($canEditNotes)
          @can('update', [$meeting, $project])
            <div class="collapse {{ $isEditingNotes ? 'show' : '' }}" id="{{ $notesEditId }}">
              <form method="POST" action="{{ route('projects.meetings.updateNotes', [$project, $meeting]) }}"
                class="mt-2">
                @csrf
                @method('PATCH')
                <input type="hidden" name="form_context" value="meeting-notes">
                <input type="hidden" name="meeting_id" value="{{ $meeting->id }}">

                <label for="{{ $notesEditId }}-textarea" class="sr-only">Notas da reuniao</label>
                <x-form.textarea name="meeting_notes" :id="$notesEditId . '-textarea'" :value="$meeting->notes" groupClass="mb-2" rows="3"
                  maxlength="10000" />

                <div class="d-flex justify-content-end" style="gap: 0.5rem;">
                  <x-form.cancel-button class="btn-sm" data-toggle="collapse"
                    data-target="#{{ $notesDisplayId }}, #{{ $notesEditId }}" />
                  <x-form.save-button class="btn btn-primary btn-sm" />
                </div>
              </form>
            </div>
          @endcan
        @endif
      </li>
    </ul>
  </div>
</div>
