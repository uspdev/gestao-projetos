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
  $hasNotes = filled($item->notes);

  if ($discussable) {
      $morphClass = $discussable->getMorphClass();

      $typeLabel = match ($morphClass) {
          'project' => 'Projeto',
          'task' => 'Tarefa',
          default => ucfirst($morphClass),
      };

      $title = $discussable->title ?? ($discussable->name ?? "Item #{$discussable->id}");

      $link = match ($morphClass) {
          'task' => deep_link('tasks.show', $discussable),
          'project' => deep_link('projects.show', $discussable),
          default => null,
      };
  } elseif (filled($item->title)) {
      $typeLabel = 'Item independente';
      $title = $item->title;
  }
@endphp

<li id="{{ deep_link_fragment($item) }}" class="list-group-item px-0 py-2"
  tabindex="-1" data-deep-link-target data-deep-link-expand="{{ $notesCollapseId }}">
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

          @if ($hasNotes)
            <i class="fa fa-sticky-note text-warning" title="Este item possui anotações"
              aria-label="Este item possui anotações"></i>
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

  <div class="collapse mt-2 meeting-item-notes-collapse" id="{{ $notesCollapseId }}">
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
          <x-markdown.markdown-content :text="$item->notes" />
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
                <x-form.textarea name="notes" id="{{ $notesEditCollapseId }}-textarea" groupClass="mb-0" markdown-profile="full"
                  value="{{ old('notes', $item->notes) }}" rows="4" maxlength="10000" class="mb-0"
                  data-file-reference-url="{{ route('files.selectable', ['context_type' => 'meeting_item', 'context_id' => $item->id]) }}"
                  data-file-share-url="{{ route('meetings.file-shares.store', $meeting) }}"
                  data-mention-search-url="{{ route('mentions.selectable', ['context_type' => 'meeting_item', 'context_id' => $item->id]) }}" />
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
      var notesToggleAll = document.getElementById('meeting-notes-toggle-all');
      var noteCollapses = Array.prototype.slice.call(document.querySelectorAll('.meeting-item-notes-collapse'));
      // Verifica se um collapse está atualmente expandido
      function isShown(collapse) {
        return collapse.classList.contains('show');
      }
      // Atualiza o estado do botão "Toggle All" (ícone, texto e atributos ARIA) com base no estado de expansão das anotações
      function setToggleAllState(expanded) {
        if (!notesToggleAll) {
          return;
        }

        var closedIcon = notesToggleAll.querySelector('.meeting-notes-toggle-all-icon-closed');
        var openIcon = notesToggleAll.querySelector('.meeting-notes-toggle-all-icon-open');
        var text = notesToggleAll.querySelector('.meeting-notes-toggle-all-text');
        var label = expanded ? 'Recolher anotações' : 'Expandir anotações';

        notesToggleAll.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        notesToggleAll.setAttribute('title', label);
        notesToggleAll.setAttribute('aria-label', label);

        if (closedIcon) {
          closedIcon.classList.toggle('d-none', expanded);
        }

        if (openIcon) {
          openIcon.classList.toggle('d-none', !expanded);
        }

        if (text) {
          text.textContent = label;
        }
      }
      // Sincroniza o estado do botão "Toggle All" com base no estado individual das anotações
      function syncToggleAllState() {
        var allExpanded = noteCollapses.length > 0 && noteCollapses.every(isShown);
        setToggleAllState(allExpanded);
      }
      // Sincroniza o ícone de cada botão de toggle individual com o estado da respectiva anotação
      function syncNotesToggle(button) {
        var icon = button.querySelector('.meeting-notes-toggle-icon');
        var collapse = document.getElementById(button.getAttribute('aria-controls'));

        if (!icon || !collapse) {
          return;
        }

        icon.textContent = isShown(collapse) ? '▾' : '▸';
      }

      document.querySelectorAll('.meeting-notes-toggle').forEach(function(button) {
        var collapse = document.getElementById(button.getAttribute('aria-controls'));

        button.addEventListener('click', function() {
          window.setTimeout(function() {
            syncNotesToggle(button);
            syncToggleAllState();
          }, 0);
        });

        if (collapse && window.jQuery) {
          window.jQuery(collapse).on('shown.bs.collapse hidden.bs.collapse', function() {
            syncNotesToggle(button);
            syncToggleAllState();
          });
        }

        syncNotesToggle(button);
      });

      // Toggle todas as anotações de uma vez
      if (notesToggleAll) {
        notesToggleAll.addEventListener('click', function() {
          var shouldExpand = !noteCollapses.every(isShown);

          setToggleAllState(shouldExpand);

          if (window.jQuery) {
            window.jQuery(noteCollapses).collapse(shouldExpand ? 'show' : 'hide');
          } else {
            noteCollapses.forEach(function(collapse) {
              collapse.classList.toggle('show', shouldExpand);
            });
          }

          window.setTimeout(function() {
            document.querySelectorAll('.meeting-notes-toggle').forEach(syncNotesToggle);
            syncToggleAllState();
          }, 350);
        });
      }

      syncToggleAllState();
      });
    </script>
  @endpush
@endonce
