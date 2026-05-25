@php
  $discussable = $item->discussable;
  $title = 'Item removido';
  $link = null;
  $typeLabel = 'Outro';
  $canEditNotes = $canEditNotes ?? false;
  $notesCollapseId = 'meeting-item-notes-' . $item->id;
  $notesDisplayId = 'meeting-item-notes-display-' . $item->id;
  $notesEditCollapseId = 'meeting-item-notes-edit-' . $item->id;

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
            <span class="font-weight-bold text-muted">{{ $title }}</span>
          @endif

          <button type="button" class="btn btn-link btn-sm p-0 text-primary meeting-notes-toggle"
            data-toggle="collapse" data-target="#{{ $notesCollapseId }}" aria-expanded="false"
            aria-controls="{{ $notesCollapseId }}" title="Alternar notas" aria-label="Alternar notas">
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

  <div class="collapse mt-2" id="{{ $notesCollapseId }}">
    <div class="border rounded bg-light p-2">
      <div class="d-flex align-items-center justify-content-between mb-2">
        <small class="text-muted">Notas</small>

        @if ($canEditNotes)
          @can('update', [$meeting, $project])
            <button type="button" class="btn btn-outline-primary btn-sm py-0" data-toggle="collapse"
              data-target="#{{ $notesDisplayId }}, #{{ $notesEditCollapseId }}" aria-expanded="false"
              aria-controls="{{ $notesDisplayId }} {{ $notesEditCollapseId }}">
              <i class="fas fa-pen"></i> Editar
            </button>
          @endcan
        @endif
      </div>

      <div class="collapse show" id="{{ $notesDisplayId }}">
        @if (filled($item->notes))
          <x-markdown-content :text="$item->notes" />
        @else
          <div class="text-muted">Sem notas.</div>
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
                <label for="{{ $notesEditCollapseId }}-textarea" class="sr-only">Notas do item</label>
                <textarea name="notes" id="{{ $notesEditCollapseId }}-textarea" rows="4" maxlength="10000"
                  class="form-control">{{ old('notes', $item->notes) }}</textarea>
              </div>
              <div class="d-flex justify-content-end" style="gap: 0.5rem;">
                <button type="button" class="btn btn-light btn-sm" data-toggle="collapse"
                  data-target="#{{ $notesDisplayId }}, #{{ $notesEditCollapseId }}">
                  Cancelar
                </button>
                <button type="submit" class="btn btn-primary btn-sm">
                  <i class="fas fa-save"></i> Salvar notas
                </button>
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
