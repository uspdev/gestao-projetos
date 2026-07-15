@php
  $discussable = $item->discussable;
  $title = 'Item removido';
  $link = null;
  $typeLabel = 'Outro';
  $canEditNotes = $canEditNotes ?? false;
  $canEditTitle = $item->title !== null
      && $meeting->status !== \App\Enums\Meeting\MeetingStatus::COMPLETED;
  $notesCollapseId = 'meeting-item-notes-' . $item->id;
  $notesDisplayId = 'meeting-item-notes-display-' . $item->id;
  $notesEditCollapseId = 'meeting-item-notes-edit-' . $item->id;
  $titleDisplayId = 'meeting-item-title-display-' . $item->id;
  $titleEditId = 'meeting-item-title-edit-' . $item->id;

  if ($discussable) {
      $morphClass = $discussable->getMorphClass();

      $typeLabel = match ($morphClass) {
          'project' => 'Projeto',
          'task' => 'Tarefa',
          default => ucfirst($morphClass),
      };

      $title = $discussable->title ?? ($discussable->name ?? "Item #{$discussable->id}");

      $link = match ($morphClass) {
          'task' => route('tasks.show', $discussable),
          'project' => route('projects.show', $discussable),
          default => null,
      };
  } elseif (filled($item->title)) {
      $typeLabel = 'Item independente';
      $title = $item->title;
  }
@endphp

<li class="list-group-item px-0 py-2">
  <div class="d-flex align-items-start justify-content-between gap-3">
    <div class="d-flex align-items-start" style="gap: 0.75rem;">

      <div class="badge badge-light border text-muted px-2 py-1">#{{ $item->order }}</div>

      <div>
        <div class="d-flex align-items-center flex-wrap" style="gap: 0.5rem;">
          <span class="badge badge-light border text-dark">{{ $typeLabel }}</span>

          @if ($link)
            <a href="{{ $link }}" class="font-weight-bold text-dark">
              {{ $title }}
            </a>
          @else
            <span class="font-weight-bold text-muted" id="{{ $titleDisplayId }}">{{ $title }}</span>
          @endif

          @if ($canEditTitle)
            @can('update', [$meeting, $project])
              <button type="button" class="btn btn-link btn-sm p-0" data-toggle="collapse"
                data-target="#{{ $titleEditId }}" aria-label="Editar título do item">
                <i class="fas fa-pen"></i>
              </button>
            @endcan
          @endif

          <button type="button" class="btn btn-link btn-sm p-0 text-primary meeting-notes-toggle"
            data-toggle="collapse" data-target="#{{ $notesCollapseId }}" aria-expanded="false"
            aria-controls="{{ $notesCollapseId }}" title="Alternar Anotações prévias" aria-label="Alternar Anotações prévias">
            <span class="meeting-notes-toggle-icon" aria-hidden="true"
              style="font-size: 1.25rem; font-weight: 700; line-height: 1;">▸</span>
          </button>
        </div>
      </div>

    </div>

    <div class="d-flex align-items-center" style="gap: 0.25rem;">
      @if ($canRemove)
        @can('update', [$meeting, $project])
          <form method="POST" action="{{ route('projects.meetings.items.destroy', [$project, $meeting, $item]) }}"
            class="d-inline-block" onsubmit="return confirm('Deseja remover este item de pauta?');">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn btn-outline-danger btn-sm py-0" aria-label="Remover item">
              <i class="fas fa-trash"></i>
            </button>
          </form>
        @endcan
      @endif
    </div>
  </div>

  @if ($canEditTitle)
    @can('update', [$meeting, $project])
      <div class="collapse mt-2" id="{{ $titleEditId }}">
        <form method="POST" action="{{ route('projects.meetings.items.update', [$project, $meeting, $item]) }}">
          @csrf
          @method('PATCH')
          <label for="{{ $titleEditId }}-input" class="sr-only">Título do item</label>
          <input type="text" name="title" id="{{ $titleEditId }}-input" class="form-control form-control-sm mb-2"
            value="{{ $item->title }}" maxlength="255" required>
          <div class="d-flex justify-content-end" style="gap: 0.5rem;">
            <x-form.cancel-button class="btn-sm" data-toggle="collapse" data-target="#{{ $titleEditId }}" />
            <x-form.save-button class="btn btn-primary btn-sm" />
          </div>
        </form>
      </div>
    @endcan
  @endif

  <div class="collapse mt-2" id="{{ $notesCollapseId }}">
    <div class="border rounded bg-light p-2">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <small class="text-muted">Anotações prévias do item</small>

        @if ($canEditNotes)
          @can('update', [$meeting, $project])
            <button type="button" class="btn btn-outline-primary btn-sm py-0" data-toggle="collapse"
              data-target="#{{ $notesDisplayId }}, #{{ $notesEditCollapseId }}" aria-expanded="false"
              aria-controls="{{ $notesDisplayId }} {{ $notesEditCollapseId }}">
              <i class="fas fa-pen"></i>
            </button>
          @endcan
        @endif
      </div>

      <div class="collapse show" id="{{ $notesDisplayId }}">
        @if (filled($item->notes))
          <x-markdown-content :text="$item->notes" />
        @else
          <div class="text-muted">Sem Anotações prévias.</div>
        @endif
      </div>

      @if ($canEditNotes)
        @can('update', [$meeting, $project])
          <div class="collapse" id="{{ $notesEditCollapseId }}">
            <form method="POST"
              action="{{ route('projects.meetings.items.updateNotes', [$project, $meeting, $item]) }}">
              @csrf
              @method('PATCH')
              <div class="form-group mb-2">
                <label for="{{ $notesEditCollapseId }}-textarea" class="sr-only">Anotações prévias do item</label>
                <x-form.textarea name="notes" id="{{ $notesEditCollapseId }}-textarea" groupClass="mb-0"
                  value="{{ old('notes', $item->notes) }}" rows="4" maxlength="10000" class="mb-0" />
              </div>
              <div class="d-flex justify-content-end" style="gap: 0.5rem;">
                <x-form.cancel-button class="btn-sm" data-toggle="collapse"
                  data-target="#{{ $notesDisplayId }}, #{{ $notesEditCollapseId }}" />
                <x-form.save-button class="btn btn-primary btn-sm" />
              </div>
            </form>
          </div>
        @endcan
      @endif
    </div>
  </div>

</li>

@once
  @push('scripts')
    <script>
      document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.meeting-notes-toggle').forEach(function(button) {
          var icon = button.querySelector('.meeting-notes-toggle-icon');

          if (!icon) {
            return;
          }

          function syncIcon() {
            var expanded = button.getAttribute('aria-expanded') === 'true';
            icon.textContent = expanded ? '▾' : '▸';
          }

          button.addEventListener('click', function() {
            window.setTimeout(syncIcon, 0);
          });

          syncIcon();
        });
      });
    </script>
  @endpush
@endonce
